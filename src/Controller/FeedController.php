<?php

declare(strict_types=1);

namespace Weblog\Controller;

use Weblog\Controller\Abstract\AbstractController;
use Weblog\Exception\NotFoundException;
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

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dom->loadXML($siteMap->asXML());

        $this->setHeaders(ContentType::XML);
        echo $dom->saveXML();
    }

    /**
     * Renders an RSS feed for the Weblog.
     */
    public function renderRSS(): void
    {
        $posts = $this->postRepository->fetchAllPosts();
        if ($posts->isEmpty()) {
            throw new NotFoundException();
        }
        $rss = FeedGenerator::generateRSS($posts);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dom->loadXML($rss->asXML());

        $this->setHeaders(ContentType::XML);

        echo $dom->saveXML();
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
            throw new NotFoundException();
        }
        $rss = FeedGenerator::generateRSS($posts, $category);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $dom->loadXML($rss->asXML());

        $this->setHeaders(ContentType::XML);

        echo $dom->saveXML();
    }
}
