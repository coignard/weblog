<?php

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
