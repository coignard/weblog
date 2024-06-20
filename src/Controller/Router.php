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
        $routeKey = isset($_GET['go']) && \is_string($_GET['go']) ? $_GET['go'] : 'home';

        $requestedRoute = Route::tryFrom($routeKey) ?? $routeKey;

        if (str_starts_with($routeKey, 'drafts/')) {
            try {
                $slug = substr($routeKey, 7);
                $this->postController->renderDraft($slug);
            } catch (\Weblog\Exception\NotFoundException $e) {
                $this->postController->handleNotFound();
            }
            return;
        }

        if (isset($_GET['q']) && is_string($_GET['q'])) {
            $query = urldecode($_GET['q']);
            try {
                $this->postController->renderSearchResults($query);
            } catch (\Weblog\Exception\NotFoundException $e) {
                $this->postController->handleNotFound();
            }
            return;
        }

        try {
            match ($requestedRoute) {
                Route::HOME => $this->postController->renderHome(),
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
