<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;
use Weblog\Controller\FeedController;
use Weblog\Controller\PostController;
use Weblog\Controller\Router;
use Weblog\Model\PostRepository;

final class Factory
{
    /**
     * Create new Router instance.
     */
    public static function createRouter(): Router
    {
        return new Router(self::createPostController(), self::createFeedController());
    }

    private static function createPostController(): PostController
    {
        return new PostController(self::createPostRepostory());
    }

    private static function createFeedController(): FeedController
    {
        return new FeedController(self::createPostRepostory());
    }

    private static function createPostRepostory(): PostRepository
    {
        return new PostRepository(Config::get()->weblogDir);
    }
}
