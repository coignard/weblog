<?php

declare(strict_types=1);

namespace Weblog\Model\Enum;

/**
 * Defines possible values of beautify config.
 */
enum Beautify: string
{
    case OFF = 'Off';
    case ALL = 'All';
    case CONTENT = 'Content';
    case RSS = 'RSS';
}
