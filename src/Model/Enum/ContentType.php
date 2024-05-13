<?php

declare(strict_types=1);

namespace Weblog\Model\Enum;

/**
 * Defines content types used within the weblog system.
 */
enum ContentType: string
{
    case TEXT = 'text/plain';
    case XML = 'application/xml';
}
