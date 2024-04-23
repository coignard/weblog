<?php

declare(strict_types=1);

namespace Weblog\Controller;

use Weblog\Config;
use Weblog\Controller\Abstract\AbstractController;
use Weblog\Model\Entity\Post;
use Weblog\Model\PostCollection;
use Weblog\Model\Enum\ShowUrls;
use Weblog\Utils\ContentFormatter;
use Weblog\Utils\StringUtils;
use Weblog\Utils\TextUtils;
use Weblog\Utils\Validator;

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
     * @param PostCollection $posts defaults to all
     * @param bool $showUrls indicates if we should append URLs to each post
     * @param bool $isPostNewline indicates if we should display additional newlines between posts (could be refactored)
     */
    public function renderPosts(?PostCollection $posts = null, bool $showUrls = false, bool $isPostNewline = false): void
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

        echo Config::get()->showPoweredBy ? "\n\n\n\n" : "\n\n\n";
    }

    /**
     * Retrieves the requested post based on the GET parameter, converting the title to a slug and handling .txt extension.
     *
     * @param string $postSlug the post's slug
     *
     * @return Post|null the post of the requested post or null if not found
     */
    public function getRequestedPost(string $postSlug): ?Post
    {
        $postSlug = StringUtils::sanitize($postSlug);

        if (isset(Config::get()->rewrites[rtrim($postSlug, '/')])) {
            $redirectUrl = Config::get()->rewrites[$postSlug];
            if (str_starts_with($redirectUrl, 'http://') || str_starts_with($redirectUrl, 'https://')) {
                header('Location: '.$redirectUrl, true, 301);
            } else {
                header('Location: '.Config::get()->url.'/'.$redirectUrl.'/', true, 301);
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
    public function renderPost(Post $post, bool $showUrls = false): void
    {
        $date = $post->getDate()->format('d F Y');

        $header = ContentFormatter::formatPostHeader($post->getTitle(), $post->getCategory(), $date);
        echo $header."\n\n\n";

        echo ContentFormatter::formatPostContent($post->getContent());

        if ($showUrls && ShowUrls::FULL === Config::get()->showUrls) {
            $url = StringUtils::formatUrl($post->getSlug());
            echo "\n   ".$url."\n\n";
        }
    }

    /**
     * Renders a single post, to be used in the full post view.
     */
    public function renderFullPost(Post $post): void
    {
        echo "\n\n\n";
        $this->renderPost($post);
        echo Config::get()->showPoweredBy ? "\n\n\n\n" : "\n\n\n";
        $this->renderFooter($post->getDate()->format('Y'));
    }

    /**
     * Renders posts filtered by category.
     *
     * @param string $category category name from URL
     */
    public function renderPostsByCategory(string $category): void
    {
        if (false === Validator::isValidCategoryPath($category)) {
            $this->handleNotFound();
        }

        $posts = $this->postRepository->fetchPostsByCategory($category);

        if ($posts->isEmpty()) {
            $this->handleNotFound();
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
        list($date, $precision) = StringUtils::extractDateFromPath($datePath);

        if (null === $date) {
            $this->handleNotFound();
            return;
        }
    

        $posts = $this->postRepository->fetchPostsByDate($date, $precision);
    
        if ($posts->isEmpty()) {
            $this->handleNotFound();
            return;
        }
        $this->renderPosts($posts, false, true);
        $this->renderFooter($date->format('Y'));
    }
    

    /**
     * Renders a random post from all available posts.
     */
    public function renderRandomPost(): void
    {
        $posts = $this->postRepository->fetchAllPosts();

        if ($posts->isEmpty()) {
            $this->handleNotFound();

            return;
        }

        $randomPost = $posts->getRandomPost();
        $this->renderFullPost($randomPost);
    }
}
