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

        try {
            match ($requestedRoute) {
                Route::HOME => $this->postController->renderHome(),
                Route::SITEMAP => $this->feedController->renderSitemap(),
                Route::RSS => $this->feedController->renderRSS(),
                Route::RANDOM => $this->postController->renderRandomPost(),
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

        $this->postController->handleNotFound();
    }
}
