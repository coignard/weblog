<?php

declare(strict_types=1);

namespace Weblog\Model;

use Weblog\Model\Entity\Post;
use Weblog\Utils\Validator;
use Weblog\Utils\StringUtils;

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
                $post = Post::createFromFile($file);
                if (!$post->isDraft()) {
                    $posts->add($post);
                }
            }
        }
        $posts->sort();

        return $posts;
    }

    /**
     * Retrieves the specific post based on the requested slug.
     *
     * @param string      $slug      the slug of the post to find
     * @param null|string $directory the path of the directory to search
     *
     * @return null|Post the file info of the requested post or null if not found
     */
    public function fetchPostInDirectory(string $slug, ?string $directory = null): ?Post
    {
        $this->setDirectory($directory ?? $this->directory);
        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $post = Post::createFromFile($file);
                if ($slug === $post->getSlug() && !$post->isDraft()) {
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
    public function fetchPostsByDate(\DateTimeImmutable $date, string $precision): PostCollection
    {
        $posts = $this->fetchAllPosts();

        return $posts->filter(static function (Post $post) use ($date, $precision) {
            switch ($precision) {
                case 'year':
                    return $date->format('Y') === $post->getDate()->format('Y');
                case 'month':
                    return $date->format('Y-m') === $post->getDate()->format('Y-m');
                case 'day':
                    return $date->format('Y-m-d') === $post->getDate()->format('Y-m-d');
                default:
                    return false;
            }
        });
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
                    $post = Post::createFromFile($file);
                    if (!$post->isDraft()) {
                        $posts->add($post);
                    }
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
     * Fetches posts from a specific date.
     *
     * @param \DateTimeImmutable $date The date to start from
     *
     * @return PostCollection returns a collection of posts from the specified date
     */
    public function fetchPostsFromDate(\DateTimeImmutable $date): PostCollection
    {
        $posts = new PostCollection();
        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $post = Post::createFromFile($file);
                if ($post->getDate() >= $date) {
                    $posts->add($post);
                }
            }
        }
        $posts->sort();

        return $posts;
    }

    /**
     * Fetches posts from a specific date range.
     *
     * @param \DateTimeImmutable $startDate The start date of the range
     * @param \DateTimeImmutable $endDate The end date of the range
     *
     * @return PostCollection returns a collection of posts from the specified date range
     */
    public function fetchPostsFromDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): PostCollection
    {
        $posts = new PostCollection();
        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $post = Post::createFromFile($file);
                if ($post->getDate() >= $startDate && $post->getDate() <= $endDate && !$post->isDraft()) {
                    $posts->add($post);
                }
            }
        }
        $posts->sort();

        return $posts;
    }

    /**
     * Fetches selected posts from the weblog directory.
     *
     * A selected post is identified by an asterisk (*) at the beginning of its title.
     * The method collects all such posts and returns them sorted from newest to oldest.
     *
     * @return PostCollection an array of Post objects inside a PostCollection
     */
    public function fetchSelectedPosts(): PostCollection
    {
        $posts = new PostCollection();
        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $post = Post::createFromFile($file);
                if ($post->isSelected() && !$post->isDraft()) {
                    $posts->add($post);
                }
            }
        }
        $posts->sort();

        return $posts;
    }

    /**
     * Searches posts by query.
     *
     * @param string $query the search query
     *
     * @return PostCollection returns a collection of posts matching the query
     */
    public function searchPosts(string $query): PostCollection
    {
        $posts = new PostCollection();
        foreach ($this->iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $post = Post::createFromFile($file);
                if (!$post->isDraft() &&
                    (StringUtils::containsIgnoreCaseAndDiacritics($post->getTitle(), $query) ||
                     StringUtils::containsIgnoreCaseAndDiacritics($post->getContent(), $query))
                ) {
                    $posts->add($post);
                }
            }
        }
        $posts->sort();

        return $posts;
    }

    /**
     * Fetches a draft by its slug.
     *
     * @param string $slug The slug of the draft to find.
     * @return null|Post The draft post or null if not found.
     */
    public function fetchDraftBySlug(string $slug): ?Post
    {
        $draftsDir = $this->directory . '/drafts';
        if (!is_dir($draftsDir)) {
            throw new \RuntimeException("Drafts directory not found: {$draftsDir}");
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($draftsDir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $post = Post::createFromFile($file, true);
                if ($slug === $post->getSlug()) {
                    return $post;
                }
            }
        }
        return null;
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
