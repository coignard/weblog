<?php

declare(strict_types=1);

namespace Weblog\Model\Enum;

/**
 * Defines possible values of show_urls config.
 */
enum ShowUrls: string
{
    case OFF = 'Off';
    case FULL = 'Full';
    case SHORT = 'Short';
}
