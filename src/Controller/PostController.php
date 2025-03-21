<?php

/**
 * This file is part of the Weblog.
 *
 * Copyright (c) 2024-2025  René Coignard <contact@renecoignard.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Weblog\Controller;

use Weblog\Config;
use Weblog\Controller\Abstract\AbstractController;
use Weblog\Exception\NotFoundException;
use Weblog\Model\Entity\Post;
use Weblog\Model\Enum\ShowUrls;
use Weblog\Model\PostCollection;
use Weblog\Utils\ContentFormatter;
use Weblog\Utils\StringUtils;
use Weblog\Utils\TextUtils;
use Weblog\Utils\HttpUtils;

final class PostController extends AbstractController
{
    /**
     * Renders the home page.
     */
    public function renderHome(): void
    {
        echo TextUtils::formatAboutHeader();
        echo TextUtils::formatAboutText();

        $this->renderPosts();
        $this->renderFooter();
    }

    /**
     * Displays posts.
     *
     * @param PostCollection $posts         defaults to all
     * @param bool           $showUrls      indicates if we should append URLs to each post
     * @param bool           $isPostNewline indicates if we should display additional newlines between posts (could be refactored)
     */
    public function renderPosts(?PostCollection $posts = null, string $showUrls = 'Off', bool $isPostNewline = false): void
    {
        if (null === $posts) {
            $posts = $this->postRepository->fetchAllPosts();
        }

        $lastIndex = $posts->count() - 1;
        foreach ($posts as $index => $post) {
            if (!$post instanceof Post) {
                continue;
            }

            if ($isPostNewline) {
                echo "\n\n\n\n";
            }

            $this->renderPost($post, $showUrls);

            if ($index !== $lastIndex && !$isPostNewline) {
                echo "\n\n\n\n";
            }
        }
    }

    /**
     * Retrieves the requested post based on the GET parameter, converting the title to a slug and handling .txt extension.
     *
     * @param string $postSlug the post's slug
     *
     * @return null|Post the post of the requested post or null if not found
     */
    public function getRequestedPost(string $postSlug): ?Post
    {
        $postSlug = StringUtils::sanitize($postSlug);

        if (isset(Config::get()->rewrites[rtrim($postSlug, '/')])) {
            $redirectUrl = Config::get()->rewrites[$postSlug];
            if (str_starts_with($redirectUrl, 'http://') || str_starts_with($redirectUrl, 'https://')) {
                HttpUtils::redirect($redirectUrl, 301);
            } else {
                HttpUtils::redirect(Config::get()->url . '/' . $redirectUrl . '/', 301);
            }

            exit;
        }

        return $this->postRepository->fetchPostInDirectory($postSlug);
    }

    /**
     * Renders a single post, including its header, content, and optionally a URL.
     *
     * @param bool $showUrls indicates if we should append URLs to each post
     */
    public function renderPost(Post $post, string $showUrls = 'Off'): void
    {
        $title = ltrim($post->getTitle(), '.');
        $category = ltrim($post->getCategory(), '.');
        $date = $post->getDate()->format('j F Y');

        $header = ContentFormatter::formatPostHeader($title, $category, $date);

        echo $header."\n\n\n";

        echo ContentFormatter::formatPostContent($post->getContent());

        if ($showUrls && (ShowUrls::FULL === Config::get()->showUrls | ShowUrls::SHORT === Config::get()->showUrls)) {
            $url = StringUtils::formatUrl($post->getSlug());
            echo "\n   ".$url."\n\n";
        }
    }

    /**
     * Renders a single post, to be used in the full post view.
     */
    public function renderFullPost(Post $post): void
    {
        echo "\n\n\n\n";
        $this->renderPost($post);
        $this->renderFooter($post->getDate()->format('Y'));
    }

    /**
     * Renders a draft post.
     *
     * @param string $slug The slug of the draft to render.
     * @throws NotFoundException If the draft is not found.
     */
    public function renderDraft(string $slug): void
    {
        $draft = $this->postRepository->fetchDraftBySlug($slug);
        if (null === $draft) {
            throw new NotFoundException();
        }
        $this->renderFullPost($draft);
    }

    /**
     * Renders posts filtered by category.
     *
     * @param string $category category name from URL
     */
    public function renderPostsByCategory(string $category): void
    {
        $posts = $this->postRepository->fetchPostsByCategory($category);

        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }

        echo "\n\n\n\n";
        $this->renderPosts($posts);
        $this->renderFooter($posts->getYearRange());
    }

    /**
     * Renders posts filtered by date.
     *
     * @param string $datePath date path from URL in format yyyy/mm/dd or yyyy/mm or yyyy
     */
    public function renderPostsByDate(string $datePath): void
    {
        [$date, $precision] = StringUtils::extractDateFromPath($datePath);

        if (null === $date) {
            throw new NotFoundException();
        }

        $posts = $this->postRepository->fetchPostsByDate($date, $precision);

        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $this->renderPosts($posts, 'Off', true);
        $this->renderFooter($date->format('Y'));
    }

    /**
     * Renders a random post from all available posts.
     */
    public function renderRandomPost(): void
    {
        $posts = $this->postRepository->fetchAllPosts();

        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }

        $randomPost = $posts->getRandomPost();
        $this->renderFullPost($randomPost);
    }

    /**
     * Renders the latest post.
     */
    public function renderLatestPost(): void
    {
        $posts = $this->postRepository->fetchAllPosts();
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $latestPost = $posts->getFirstPost();
        $this->renderFullPost($latestPost);
    }

    /**
     * Renders posts from the last year.
     */
    public function renderLatestYear(): void
    {
        $startOfYear = new \DateTimeImmutable('first day of January this year 00:00:00');
        $today = new \DateTimeImmutable('now');

        $posts = $this->postRepository->fetchPostsFromDateRange($startOfYear, $today);
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $this->renderPosts($posts, 'Off', true);
        $this->renderFooter($posts->getYearRange());
    }

    /**
     * Renders posts from the last month.
     */
    public function renderLatestMonth(): void
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $today = new \DateTimeImmutable('now');

        $posts = $this->postRepository->fetchPostsFromDateRange($startOfMonth, $today);
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $this->renderPosts($posts, 'Off', true);
        $this->renderFooter($posts->getYearRange());
    }

    /**
     * Renders posts from the last week.
     */
    public function renderLatestWeek(): void
    {
        $today = new \DateTimeImmutable('now');
        $startOfWeek = $today->modify('monday this week 00:00:00');
        $endOfWeek = $today;

        $posts = $this->postRepository->fetchPostsFromDateRange($startOfWeek, $endOfWeek);
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $this->renderPosts($posts, 'Off', true);
        $this->renderFooter($posts->getYearRange());
    }

    /**
     * Renders posts from the last day.
     */
    public function renderLatestDay(): void
    {
        $today = new \DateTimeImmutable('today 00:00:00');
        $now = new \DateTimeImmutable('now');

        $posts = $this->postRepository->fetchPostsFromDateRange($today, $now);
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $this->renderPosts($posts, 'Off', true);
        $this->renderFooter($posts->getYearRange());
    }

    /**
     * Renders the selected posts.
     *
     * Fetches and displays all posts marked as selected. If no selected posts are found, a NotFoundException is thrown.
     *
     * @throws NotFoundException if no selected posts are found.
     */
    public function renderSelectedPosts(): void
    {
        $posts = $this->postRepository->fetchSelectedPosts();
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $this->renderPosts($posts, 'Off', true);
        $this->renderFooter($posts->getYearRange());
    }

    /**
     * Renders search results.
     *
     * @param string $query the search query
     */
    public function renderSearchResults(string $query): void
    {
        $posts = $this->postRepository->searchPosts($query);
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $this->renderPosts($posts, 'Off', true);
        $this->renderFooter($posts->getYearRange());
    }
}
