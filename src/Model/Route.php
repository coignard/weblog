<?php

declare(strict_types=1);

namespace Weblog\Model;

enum Route: string
{
    case HOME = 'home';
    case SITEMAP = 'sitemap.xml';
    case RSS = 'rss';
    case RANDOM = 'random';
}
