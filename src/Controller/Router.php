<?php

declare(strict_types=1);

namespace Weblog\Controller;

use Weblog\Model\Route;
use Weblog\Utils\StringUtils;
use Weblog\Utils\Validator;

final class Router
{
    public function __construct(
        private readonly PostController $postController,
        private readonly FeedController $feedController,
    ) {}

    /**
     * Routes the request based on server parameters using a predefined set of routes.
     */
    public function route(): void
    {
        $routeKey = isset($_GET['go']) && is_string($_GET['go']) ? $this->sanitizeRouteKey($_GET['go']) : null;

        $requestedRoute = $routeKey !== null ? (Route::tryFrom($routeKey) ?? $routeKey) : null;

        if ($routeKey === null) {
            $this->postController->renderHome();
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
        } catch (\Exception) {
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
