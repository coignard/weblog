<?php

declare(strict_types=1);

namespace Weblog\Model;

use Weblog\Model\Entity\Post;
use Weblog\Utils\Validator;

final class PostRepository
{
    private \RecursiveIteratorIterator $iterator;

    /**
     * Constructor for PostRepository.
     * Initializes the iterator based on the given directory or the default directory specified in configuration.
     *
     * @param string $directory The directory path to initialize the iterator
     */
    public function __construct(private string $directory)
    {
        $this->setDirectory($directory);
        $this->loadIterator();
    }

    /**
     * Fetches all posts from the weblog directory, sorted from newest to oldest.
     *
     * @return PostCollection an array of Posts objects inside a PostCollection
     */
    public function fetchAllPosts(): PostCollection
    {
        $posts = new PostCollection();
        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $posts->add(Post::createFromFile($file));
            }
        }

        $posts->sort();

        return $posts;
    }

    /**
     * Retrieves the specific post based on the requested slug.
     *
     * @param string      $slug      the slug of the post to find
     * @param string|null $directory the path of the directory to search
     *
     * @return Post|null the file info of the requested post or null if not found
     */
    public function fetchPostInDirectory(string $slug, ?string $directory = null): ?Post
    {
        $this->setDirectory($directory ?? $this->directory);

        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $post = Post::createFromFile($file);
                if ($slug === $post->getSlug()) {
                    return $post;
                }
            }
        }

        return null;
    }

    /**
     * Fetches posts filtered by a specific date. If no date is provided, the current date is used.
     *
     * @param \DateTimeImmutable $date The date to filter the posts by. Defaults to the current date if null.
     *
     * @return PostCollection returns a collection of posts that match the given date
     */
    public function fetchPostsByDate(\DateTimeImmutable $date = new \DateTimeImmutable()): PostCollection
    {
        $posts = $this->fetchAllPosts();

        return $posts->filterByDate($date);
    }

    /**
     * Fetches all posts from a specified category.
     *
     * @param string $category The category to filter posts by. 'misc' will also fetch posts that do not belong to any category.
     *
     * @return PostCollection returns a collection of posts filtered by the specified category
     */
    public function fetchPostsByCategory(string $category): PostCollection
    {
        $posts = new PostCollection();
        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                if (Validator::isValidCategoryPost($file, $category, $this->directory)) {
                    $posts->add(Post::createFromFile($file));
                }
            }
        }

        $posts->sort();

        return $posts;
    }

    /**
     * Retrieves the range of years (earliest and latest) from all posts.
     *
     * @return string range of years for all posts
     */
    public function getPostYearsRange(): string
    {
        $posts = $this->fetchAllPosts();

        return $posts->getYearRange();
    }

    /**
     * Sets the directory for the iterator and resets the iterator to reflect the new directory.
     * This is necessary to ensure the iterator points to the correct directory.
     *
     * @param string $newDirectory the new directory path to set
     */
    public function setDirectory(string $newDirectory): void
    {
        if (!is_dir($newDirectory)) {
            throw new \InvalidArgumentException("The specified directory does not exist or is not a directory: {$newDirectory}");
        }

        $this->directory = $newDirectory;
    }

    private function loadIterator(): void
    {
        $directoryIterator = new \RecursiveDirectoryIterator($this->directory, \RecursiveDirectoryIterator::SKIP_DOTS);
        $this->iterator = new \RecursiveIteratorIterator($directoryIterator);
    }
}
