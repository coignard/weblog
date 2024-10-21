<?php

/**
 * This file is part of the Weblog.
 *
 * Copyright (c) 2024  René Coignard <contact@renecoignard.com>
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
use Weblog\Model\Route;
use Weblog\Utils\StringUtils;
use Weblog\Utils\Validator;
use Weblog\Utils\Logger;
use Weblog\Utils\HttpUtils;

final class Router
{
    public function __construct(
        private readonly PostController $postController,
        private readonly FeedController $feedController,
    ) {}

    /**
     * Handles URIs to perform redirection based on predefined rules.
     *
     * @param string $uri The requested URI.
     *
     * @return bool Returns true if a redirection has been made, false otherwise.
     */
    private function handleRedirectRoute(string $uri): bool
    {
        $scheme = HttpUtils::getScheme();
        $host = HttpUtils::getHost();

        // Do not redirect '/sitemap.xml'
        if ($uri === '/sitemap.xml') {
            return false;
        }

        // Normalize multiple slashes and redirect to a single slash version
        if (preg_match('#^([^.]*?\/)\/+(.*)$#', $uri, $matches)) {
            $normalizedPath = preg_replace('#/{2,}#', '/', "{$matches[1]}{$matches[2]}");
            HttpUtils::redirect("{$scheme}://{$host}{$normalizedPath}");
            return true;
        }

        // Remove trailing slash in .txt files and correct to full URL
        if (preg_match('#^/(.+)\.txt/$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/{$matches[1]}.txt");
            return true;
        }

        // Handle .txt extension for routing
        if (preg_match('#^/(.+)\.txt$#', $uri, $matches)) {
            $_GET['go'] = $matches[1];
            return false;
        }

        // Ensure names that don't end in slash are redirected with a slash
        if (preg_match('#^/([^/]+)$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/{$matches[1]}/");
            return true;
        }

        // Year paths addition of trailing slash with full URL
        if (preg_match('#^/(\d{4})$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/{$matches[1]}/");
            return true;
        }

        // Ensure year/month paths end with slash with full URL
        if (preg_match('#^/(\d{4})/(\d{2})$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/{$matches[1]}/{$matches[2]}/");
            return true;
        }

        // Ensure year/month/day paths end with slash with full URL
        if (preg_match('#^/(\d{4})/(\d{2})/(\d{2})$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/{$matches[1]}/{$matches[2]}/{$matches[3]}/");
            return true;
        }

        // Ensure RSS category paths are canonical with full URL
        if (preg_match('#^/rss/([\w-]+)$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/rss/{$matches[1]}/");
            return true;
        }

        // Normalize latest path with trailing slash
        if (preg_match('#^/latest$#', $uri)) {
            HttpUtils::redirect("{$scheme}://{$host}/latest/");
            return true;
        }

        // Normalize latest subpaths with trailing slash
        if (preg_match('#^/latest/([^/]+)$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/latest/{$matches[1]}/");
            return true;
        }

        // Normalize search paths with trailing slash
        if (preg_match('#^/search/(.*[^/])$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/search/{$matches[1]}/");
            return true;
        }

        // Handle "/search" path setting 'go' GET parameter
        if (preg_match('#^/search/(.*)/$#', $uri, $matches)) {
            $_GET['go'] = "search/{$matches[1]}";
            return false;
        }

        // Normalize selected path with trailing slash
        if (preg_match('#^/selected$#', $uri)) {
            HttpUtils::redirect("{$scheme}://{$host}/selected/");
            return true;
        }

        // Normalize draft paths with trailing slash
        if (preg_match('#^/drafts/([^/]+)$#', $uri, $matches)) {
            HttpUtils::redirect("{$scheme}://{$host}/drafts/{$matches[1]}/");
            return true;
        }

        return false;
    }

    /**
     * Routes the request based on server parameters using a predefined set of routes.
     */
    public function route(): void
    {
        $uri = $_SERVER['REQUEST_URI'];

        if ($this->handleRedirectRoute($uri)) {
            return;
        }

        if ($this->isFaviconRequest($_SERVER['REQUEST_URI'])) {
            $this->handleFaviconRequest();
            return;
        }

        $routeKey = isset($_GET['go']) && is_string($_GET['go']) ? $this->sanitizeRouteKey($_GET['go']) : null;

        $requestedRoute = $routeKey !== null ? (Route::tryFrom($routeKey) ?? $routeKey) : null;

        if ($routeKey === null) {
            $this->postController->renderHome();
            $this->logRequest();
            return;
        }

        try {
            match ($requestedRoute) {
                Route::SITEMAP => $this->feedController->renderSitemap(),
                Route::RSS => $this->feedController->renderRSS(),
                Route::RANDOM => $this->postController->renderRandomPost(),
                Route::LATEST => $this->postController->renderLatestPost(),
                Route::LATEST_YEAR => $this->postController->renderLatestYear(),
                Route::LATEST_MONTH => $this->postController->renderLatestMonth(),
                Route::LATEST_WEEK => $this->postController->renderLatestWeek(),
                Route::LATEST_DAY => $this->postController->renderLatestDay(),
                default => $this->handleDynamicRoute($routeKey),
            };
            $this->logRequest();
        } catch (\Exception) {
            $this->postController->handleNotFound();
        }
    }

    /**
     * Log the request using Logger.
     */
    private function logRequest(): void
    {
        $status = http_response_code();
        if (Config::get()->enableLogging && $status == 200) {
            Logger::getInstance(Config::get()->logFilePath)->log();
        }
    }

    /**
     * Checks if the request is for favicon.ico
     *
     * @param string $uri The requested URI
     * @return bool
     */
    private function isFaviconRequest(string $uri): bool
    {
        return preg_match('~^/[^/]*?/favicon\.ico$~', $uri) || preg_match('~^/favicon\.ico$~', $uri);
    }

    /**
     * Handles the favicon.ico request by serving the root favicon.ico
     */
    private function handleFaviconRequest(): void
    {
        $faviconPath = $_SERVER['DOCUMENT_ROOT'] . '/favicon.ico';

        if (file_exists($faviconPath)) {
            header('Content-Type: image/vnd.microsoft.icon');
            readfile($faviconPath);
        } else {
            $this->postController->handleNotFound();
        }
    }

    /**
     * Sanitizes the route key parameter.
     *
     * @param string $routeKey The route key to sanitize.
     * @return string The sanitized route key.
     */
    private function sanitizeRouteKey(string $routeKey): string
    {
        $normalized = \Normalizer::normalize($routeKey, \Normalizer::FORM_D);
        $sanitized = preg_replace('/[\p{Mn}\p{Me}\p{Cf}]/u', '', $normalized);
        return $sanitized;
    }

    /**
     * Sanitizes a slug parameter.
     *
     * @param string $slug The slug to sanitize.
     * @return string The sanitized slug.
     */
    private function sanitizeSlug(string $slug): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
    }

    /**
     * Handles dynamic routes not predefined in the Route enum.
     * Could be Route::Search & others instead maybe.
     *
     * @param string $route the route string from the 'go' parameter
     */
    private function handleDynamicRoute(string $route): void
    {
        if ($post = $this->postController->getRequestedPost($route)) {
            $this->postController->renderFullPost($post);

            return;
        }

        if ($category = StringUtils::extractCategoryFromRSS($route)) {
            $this->feedController->renderRSSByCategory($category);

            return;
        }

        if (Validator::isDateRoute($route)) {
            $this->postController->renderPostsByDate($route);

            return;
        }

        if (Validator::isValidCategoryPath($route)) {
            $this->postController->renderPostsByCategory($route);

            return;
        }

        if (Validator::isDraftsRoute($route)) {
            try {
                $slug = $this->sanitizeSlug(substr($route, 7));
                $this->postController->renderDraft($slug);
            } catch (\Weblog\Exception\NotFoundException $e) {
                $this->postController->handleNotFound();
            }
            return;
        }

        if (Validator::isSearchRoute($route)) {
            $matches = [];
            preg_match('#^search/(.+)$#', $route, $matches);
            $this->postController->renderSearchResults(urldecode($matches[1]));

            return;
        }

        if (Validator::isSelectedRoute($route)) {
            $this->postController->renderSelectedPosts();

            return;
        }

        $this->postController->handleNotFound();
    }
}
