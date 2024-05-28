<?php

declare(strict_types=1);

namespace Weblog\Model;

use Weblog\Model\Entity\Post;
use Weblog\Utils\Validator;

final class PostCollection implements \IteratorAggregate, \Countable
{
    /**
     * Constructs a collection of posts.
     *
     * @param Post[] $posts array of Post objects
     */
    public function __construct(private array $posts = []) {}

    /**
     * Adds a Post object to the collection.
     *
     * @param Post $post the post to add to the collection
     */
    public function add(Post $post): void
    {
        $this->posts[] = $post;
    }

    /**
     * Sorts the posts in the collection by date, from newest to oldest.
     */
    public function sort(): void
    {
        usort($this->posts, static fn ($a, $b) => $b->getDateTimestamp() - $a->getDateTimestamp());
    }

    /**
     * Checks if the post collection is empty.
     *
     * @return bool returns true if the collection is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return [] === $this->posts;
    }

    /**
     * Retrieves the most recent date from the posts in the collection.
     *
     * @return null|\DateTimeImmutable returns the date of the most recent post in the collection, or null if the collection is empty
     */
    public function getMostRecentDate(): ?\DateTimeImmutable
    {
        if ($this->isEmpty()) {
            return null;
        }

        $this->sort();

        return $this->posts[0]->getDate();
    }

    /**
     * Filters posts by date, comparing only the date part.
     *
     * @param \DateTimeImmutable $date the date to match posts against
     *
     * @return PostCollection returns a new PostCollection containing only posts that match the given date
     */
    public function filterByDate(\DateTimeImmutable $date): self
    {
        $filteredPosts = [];
        foreach ($this->posts as $post) {
            if (Validator::dateMatches($date, $post)) {
                $filteredPosts[] = $post;
            }
        }

        return new self($filteredPosts);
    }

    /**
     * Generates a string of the range of years for the posts.
     *
     * @return string a formatted string, empty if no posts are present in the
     */
    public function getYearRange(): string
    {
        if (empty($this->posts)) {
            return '';
        }

        $dates = array_map(static fn (Post $post) => $post->getDate(), $this->posts);

        $minYear = min($dates)->format('Y');
        $maxYear = max($dates)->format('Y');

        return $minYear === $maxYear ? $minYear : "{$minYear}-{$maxYear}";
    }

    /**
     * Selects a random post from the collection.
     *
     * @return Post the selected random post
     */
    public function getRandomPost(): Post
    {
        $randomIndex = array_rand($this->posts);

        return $this->posts[$randomIndex];
    }

    /**
     * Returns the first post in the collection.
     *
     * @return Post|null returns the first Post object in the collection, or null if the collection is empty
     */
    public function getFirstPost(): ?Post
    {
        return $this->isEmpty() ? null : $this->posts[0];
    }

    /**
     * Returns the 1-based index of the provided post in the collection or null if not found.
     *
     * @param Post $post the post to find the index of
     *
     * @return string the index of the post as a string
     */
    public function getPostIndex(Post $post): string
    {
        $reversedPosts = array_reverse($this->posts);
        $index = array_search($post, $reversedPosts, true);

        if (false === $index) {
            throw new \InvalidArgumentException('Post not found in collection');
        }

        $index = (int) $index;

        return (string) ($index + 1);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->posts);
    }

    public function count(): int
    {
        return \count($this->posts);
    }

    /**
     * @param callable $callback a callback function that returns true if the post should be included
     *
     * @return PostCollection a new collection with the filtered posts
     */
    public function filter(callable $callback): self
    {
        $filteredPosts = array_filter($this->posts, $callback);

        return new self($filteredPosts);
    }
}
