<?php

declare(strict_types=1);

namespace Weblog\Controller;

use Weblog\Controller\Abstract\AbstractController;
use Weblog\Model\Enum\ContentType;
use Weblog\Utils\FeedGenerator;

final class FeedController extends AbstractController
{
    /**
     * Renders the sitemap in XML format, listing all posts, including the main page.
     * Sorts posts from newest to oldest.
     */
    public function renderSitemap(): void
    {
        $posts = $this->postRepository->fetchAllPosts();

        $posts->sort();

        $siteMap = FeedGenerator::generateSiteMap($posts);

        $this->setHeaders(ContentType::XML);

        echo $siteMap->asXML();
    }

    /**
     * Renders an RSS feed for the Weblog.
     */
    public function renderRSS(): void
    {
        $posts = $this->postRepository->fetchAllPosts();

        if ($posts->isEmpty()) {
            $this->handleNotFound();
        }

        $rss = FeedGenerator::generateRSS($posts);

        $this->setHeaders(ContentType::XML);

        echo $rss->asXML();
    }

    /**
     * Renders an RSS feed for the given category.
     *
     * @param string $category the category to filter by
     */
    public function renderRSSByCategory(string $category): void
    {
        $posts = $this->postRepository->fetchPostsByCategory($category);

        if ($posts->isEmpty()) {
            $this->handleNotFound();
        }

        $rss = FeedGenerator::generateRSS($posts);

        $this->setHeaders(ContentType::XML);

        echo $rss->asXML();
    }
}
