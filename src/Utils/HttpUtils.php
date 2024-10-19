<?php

/**
 * This file is part of the Weblog.
 *
 * Copyright (c) 2024  René Coignard <contact@renecoignard.com>
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

/**
 * Utility class for common HTTP operations.
 */
final class HttpUtils
{
    /**
     * Redirects to a specified URL with a given status code.
     *
     * ..."Have you mooed today?"...
     *
     * @param string $url The URL to redirect to.
     * @param int    $statusCode The HTTP status code for the redirection. Default is 301 (Moved Permanently).
     * @throws \InvalidArgumentException if the provided status code is not 301 or 302.
     */
    public static function redirect(string $url, int $statusCode = 301): void
    {
        if (!in_array($statusCode, [301, 302], true)) {
            throw new \InvalidArgumentException("Invalid HTTP status code: $statusCode. Only 301 and 302 are supported.");
        }

        $messages = [
            301 => "Moved Permanently",
            302 => "Found"
        ];

        $message = $messages[$statusCode];

        header("Location: $url", true, $statusCode);

        if ($statusCode === 301 && random_int(1, 10) === 1) {
            echo <<<EOT
  _____________________
< 301 Mooed Permanently >
  ---------------------
         \   ^__^
          \  (oo)\_______
             (__)\        )\/\
                 ||----w |
                 ||     ||

EOT;

        } else {
            echo "$statusCode $message";
        }

        exit;
    }

    /**
     * Retrieves the current scheme (http or https) of the request.
     *
     * @return string The current scheme.
     */
    public static function getScheme(): string
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
    }

    /**
     * Retrieves the current host of the request.
     *
     * @return string The host.
     */
    public static function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }
}
