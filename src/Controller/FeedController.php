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
