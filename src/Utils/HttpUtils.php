<?php

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
