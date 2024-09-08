<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;

final class Logger
{
    private static ?self $instance = null;
    private string $logFilePath;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct(string $logFilePath)
    {
        $this->logFilePath = $logFilePath;
    }

    /**
     * Get the singleton instance of Logger.
     */
    public static function getInstance(string $logFilePath): self
    {
        if (null === self::$instance) {
            self::$instance = new self($logFilePath);
        }
        return self::$instance;
    }

    /**
     * Log the access information in Nginx format.
     */
    public function log(): void
    {
        if (!isset($_SERVER['REMOTE_ADDR']) || !isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SERVER_PROTOCOL'])) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $protocol = $_SERVER['SERVER_PROTOCOL'];
        $status = http_response_code();
        $size = ob_get_length();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '-';
        $referer = $_SERVER['HTTP_REFERER'] ?? '-';

        $filterWords = Config::get()->logFilterWords;
        foreach ($filterWords as $word) {
            if (stripos($uri, $word) !== false) {
                return;
            }
        }

        $filterAgents = Config::get()->logFilterAgents;
        foreach ($filterAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return;
            }
        }

        $logEntry = sprintf(
            "%s - - [%s] \"%s %s %s\" %d %d \"%s\" \"%s\"",
            $ip,
            date('d/M/Y:H:i:s O'),
            $method,
            $uri,
            $protocol,
            $status,
            $size,
            $referer,
            $userAgent
        );

        file_put_contents($this->logFilePath, $logEntry.PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
