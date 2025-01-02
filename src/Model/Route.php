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

namespace Weblog\Model;

enum Route: string
{
    case SITEMAP = 'sitemap.xml';
    case RSS = 'rss';
    case RANDOM = 'random';
    case LATEST = 'latest';
    case LATEST_YEAR = 'latest/year';
    case LATEST_MONTH = 'latest/month';
    case LATEST_WEEK = 'latest/week';
    case LATEST_DAY = 'latest/day';
}
