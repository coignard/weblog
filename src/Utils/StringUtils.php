<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;
use Weblog\Model\Enum\ShowUrls;

final class StringUtils
{
    /**
     * Converts a string to a URL-friendly slug, ensuring non-ASCII characters are appropriately replaced.
     *
     * @param string $title the string to slugify
     *
     * @return string the slugified string
     */
    public static function slugify($title): string
    {
        $title = mb_strtolower($title, 'UTF-8');
        $replacements = [
            '/а/u' => 'a',  '/б/u' => 'b',   '/в/u' => 'v',  '/г/u' => 'g',  '/д/u' => 'd',
            '/е/u' => 'e',  '/ё/u' => 'yo',  '/ж/u' => 'zh', '/з/u' => 'z',  '/и/u' => 'i',
            '/й/u' => 'y',  '/к/u' => 'k',   '/л/u' => 'l',  '/м/u' => 'm',  '/н/u' => 'n',
            '/о/u' => 'o',  '/п/u' => 'p',   '/р/u' => 'r',  '/с/u' => 's',  '/т/u' => 't',
            '/у/u' => 'u',  '/ф/u' => 'f',   '/х/u' => 'h',  '/ц/u' => 'ts', '/ч/u' => 'ch',
            '/ш/u' => 'sh', '/щ/u' => 'sch', '/ъ/u' => '',   '/ы/u' => 'y',  '/ь/u' => '',
            '/э/u' => 'e',  '/ю/u' => 'yu',  '/я/u' => 'ya',
        ];
        $title = preg_replace(array_keys($replacements), array_values($replacements), $title);

        if (null === $title) {
            throw new \RuntimeException('Failed to slugify title.');
        }

        $title = iconv('UTF-8', 'ASCII//TRANSLIT', $title) ?: '';
        $title = preg_replace('/[^a-z0-9\s-]/', '', $title) ?: '';
        $title = preg_replace('/\s+/', '-', $title ?: '');

        if (null === $title) {
            throw new \RuntimeException('Failed to slugify title.');
        }

        $title = trim($title, '-');

        if ('' === $title) {
            throw new \RuntimeException("Failed to generate a valid slug from title: {$title}");
        }

        return $title;
    }

    /**
     * Cleans a slug from extensions.
     *
     * @return string the formatted string
     */
    public static function formatAboutText(string $text): string
    {
        $text = preg_replace('/\.(\s)/', '. $1', rtrim($text));

        if (null === $text) {
            throw new \RuntimeException('Failed to format text.');
        }

        return str_replace('\\n', "\n", $text);
    }

    /**
     * Cleans a slug from extensions.
     *
     * @param string $slug the string to sanitize
     *
     * @return string the sanitized string
     */
    public static function sanitize(string $slug): string
    {
        $slug = preg_replace('/\.txt$/', '', $slug);

        if (null === $slug) {
            throw new \RuntimeException('Failed to sanitize slug.');
        }

        return rtrim($slug, '/');
    }

    /**
     * Converts escaped newline characters to actual newlines in the provided text.
     *
     * @param string $text the text to process
     *
     * @return string the text with escaped newlines converted to actual newlines
     */
    public static function sanitizeText(string $text): string
    {
        return str_replace('\\n', "\n", $text);
    }

    /**
     * Formats a URL based on the given slug and configuration settings.
     *
     * If the configuration 'show_urls' is set to 'Full', it returns the URL including the domain.
     * Otherwise, it returns a relative URL.
     *
     * @param string $slug the slug part of the URL to format
     *
     * @return string the formatted URL
     */
    public static function formatUrl(string $slug): string
    {
        return ShowUrls::FULL === Config::get()->showUrls ? Config::get()->url.'/'.$slug.'/' : '/'.$slug;
    }

    /**
     * Extracts the category name from a provided string if it matches the RSS format.
     *
     * @param string $route the input string, typically part of a URL
     *
     * @return null|string returns the category name if the pattern matches, or null if it does not
     */
    public static function extractCategoryFromRSS(string $route): ?string
    {
        if (preg_match('#^rss/([\w-]+)$#', $route, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extracts and validates the date from a path.
     *
     * This method processes a date path from URL and returns a DateTimeImmutable object
     * if the format is valid and the date is logically correct. Supports formats: yyyy/mm/dd, yyyy/mm, or yyyy.
     *
     * @param string $datePath the date path from the URL
     *
     * @return array with the date and precision
     */
    public static function extractDateFromPath(string $datePath): array
    {
        $datePath = trim($datePath, '/');
        $parts = explode('/', $datePath);
        $format = '';
        $precision = '';

        switch (\count($parts)) {
            case 1:
                $format = 'Y';
                $precision = 'year';
                break;

            case 2:
                $format = 'Y/m';
                $precision = 'month';
                break;

            case 3:
                $format = 'Y/m/d';
                $precision = 'day';
                break;
        }

        $date = \DateTimeImmutable::createFromFormat($format, $datePath);

        return [false === $date ? null : $date, $precision];
    }

    public static function capitalizeText(string $text): string
    {
        if (Config::get()->capitalizeTitles) {
            return mb_strtoupper($text, 'UTF-8');
        }

        return $text;
    }
}
