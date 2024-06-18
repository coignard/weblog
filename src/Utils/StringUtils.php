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

        $title = preg_replace_callback('/(?<=[a-z])\'(?=[a-z])/i', function() {
            return '-';
        }, $title);

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
     * Removes diacritics from the given string.
     *
     * This function replaces diacritic characters with their closest ASCII equivalents.
     *
     * @param string $text The text from which diacritics should be removed.
     * @return string The text with diacritics removed.
     */
    public static function removeDiacritics(string $text): string
    {
        $normalizeChars = array(
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', 'ŕ'=>'r'
        );

        return strtr($text, $normalizeChars);
    }

    /**
     * Checks if a string contains another string, ignoring case and diacritics.
     *
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     * @return bool Returns true if the needle is found in the haystack, false otherwise.
     */
    public static function containsIgnoreCaseAndDiacritics(string $haystack, string $needle): bool
    {
        $haystack = self::removeDiacritics(mb_strtolower($haystack, 'UTF-8'));
        $needle = self::removeDiacritics(mb_strtolower($needle, 'UTF-8'));

        return mb_strpos($haystack, $needle) !== false;
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

    /**
     * Capitalizes the provided text.
     *
     * @param string $text the text to possibly capitalize
     *
     * @return string the processed text, capitalized if the setting is enabled
     */
    public static function capitalizeText(string $text): string
    {
        if (Config::get()->capitalizeTitles) {
            return mb_strtoupper($text, 'UTF-8');
        }

        return $text;
    }

    /**
     * Beautifies the provided text by applying several transformations.
     *
     * @param string $text the text to beautify
     *
     * @return string the beautified text
     */
    public static function beautifyText(string $text): string
    {
        $prefixLength = Config::get()->prefixLength + 2;
        $prefixPattern = str_repeat(' ', $prefixLength);

        $text = preg_replace('/"([^"]*)"/', '“$1”', $text);
        $text = str_replace(' - ', ' — ', $text);
        $text = str_replace(' -', ' —', $text);
        $text = str_replace("'", "’", $text);
        $text = str_replace(['***', '* * *'], '⁂', $text);

        return $text;
    }
}
