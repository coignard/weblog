<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;
use Weblog\Model\Entity\Post;

final class Validator
{
    /**
     * Determines if the date of a post matches a specific date.
     *
     * @param \DateTimeImmutable $date the date to compare
     * @param Post               $post the post whose date is being compared
     *
     * @return bool true if the dates match, false otherwise
     */
    public static function dateMatches(\DateTimeImmutable $date, Post $post): bool
    {
        return $date->format('Y-m-d') === $post->getDate()->format('Y-m-d');
    }

    /**
     * Checks if the given route string represents a valid date pattern.
     *
     * @param string $route the route string to check
     *
     * @return bool returns true if the route matches a date pattern, false otherwise
     */
    public static function isDateRoute(string $route): bool
    {
        return (bool) preg_match('#^\d{4}(?:/\d{2}(?:/\d{2})?)?/?$#', $route);
    }

    /**
     * Determines if a file corresponds to a valid post within the specified category.
     *
     * @param \SplFileInfo $file      the file to check
     * @param string       $category  the category to match against
     * @param string       $directory The directory path
     *
     * @return bool returns true if the file is a valid post in the specified category
     */
    public static function isValidCategoryPost(\SplFileInfo $file, string $category, string $directory): bool
    {
        $filePath = str_replace('\\', '/', $file->getPathname());
        $directory = rtrim(str_replace('\\', '/', $directory), '/').'/';

        $relativePath = substr($filePath, \strlen($directory));
        $relativePath = ltrim($relativePath, '/');

        $firstDir = strstr($relativePath, '/', true) ?: $relativePath;

        if (('misc' === $category && (empty($firstDir) || 'misc' === $firstDir || $relativePath === $firstDir)) || $firstDir === $category) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the path is a valid category folder.
     *
     * @return bool returns true if the directory exists
     */
    public static function isValidCategoryPath(string $categoryPath): bool
    {
        $weblogDir = Config::get()->weblogDir;
        $fullPath = $weblogDir.('misc' !== $categoryPath ? '/'.$categoryPath : '');

        if (!is_dir($fullPath)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the current user agent is a mobile device.
     */
    public static function isMobileDevice(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (false === \is_string($userAgent)) {
            throw new \InvalidArgumentException('User agent is not a string.');
        }

        $result = preg_match('/Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $userAgent);

        if (false === $result) {
            throw new \RuntimeException('Failed to execute user agent match.');
        }

        return (bool) $result;
    }
}
