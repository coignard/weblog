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
