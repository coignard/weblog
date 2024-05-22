<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;
use Weblog\Model\Enum\Beautify;

final class ContentFormatter
{
    /**
     * Formats the content of a post into paragraphs.
     *
     * @param string $content the raw content of the post
     *
     * @return string the formatted content
     */
    public static function formatPostContent(string $content): string
    {
        $paragraphs = explode("\n", $content);
        $formattedContent = '';

        foreach ($paragraphs as $paragraph) {
            $formattedParagraph = $paragraph;
            if (!Validator::isMobileDevice()) {
                $formattedParagraph = preg_replace('/([.!?]|\.{3})(\s)/', '$1 $2', rtrim($paragraph));
            }
            $formattedContent .= TextUtils::formatParagraph($formattedParagraph ?? '')."\n";
        }

        if (in_array(Config::get()->beautify, [Beautify::ALL, Beautify::CONTENT])) {
            $formattedContent = StringUtils::beautifyText($formattedContent);
        }

        return $formattedContent;
    }

    /**
     * Formats the header of a post, including category, title, and publication date.
     * Adjusts dynamically based on device type and enabled settings.
     *
     * @param string $title    the title of the post
     * @param string $category the category of the post (optional)
     * @param string $date     the publication date of the post (optional)
     *
     * @return string the formatted header
     */
    public static function formatPostHeader($title = '', $category = '', $date = ''): string
    {
        if ('~' === substr($title, 0, 1)) {
            $title = '* * *';
        }

        if (in_array(Config::get()->beautify, [Beautify::ALL, Beautify::CONTENT])) {
            $title = StringUtils::beautifyText($title);
        }

        $lineWidth = Config::get()->lineWidth;
        $includeCategory = Config::get()->showCategory && !empty($category);
        $includeDate = Config::get()->showDate && !empty($date);

        $availableWidth = $lineWidth;
        $categoryWidth = $includeCategory ? 20 : 0;
        $dateWidth = $includeDate ? 20 : 0;
        $titleWidth = $availableWidth - $categoryWidth - $dateWidth;

        $titlePaddingLeft = (int) (($titleWidth - mb_strlen($title)) / 2);
        $titlePaddingRight = $titleWidth - mb_strlen($title) - $titlePaddingLeft;

        if (Validator::isMobileDevice() && ($titleWidth % 2) !== 0) {
            $titlePaddingLeft += 2;
        }

        $formattedTitle = str_repeat(' ', $titlePaddingLeft).$title.str_repeat(' ', $titlePaddingRight);

        $header = '';
        if ($includeCategory) {
            $header .= str_pad($category, $categoryWidth);
        }
        $header .= $formattedTitle;
        if ($includeDate) {
            $header .= str_pad($date, $dateWidth, ' ', STR_PAD_LEFT);
        }

        return StringUtils::capitalizeText($header);
    }

    /**
     * Formats the given content into RSS-compatible HTML.
     *
     * @param string $content the raw content to be formatted for RSS
     *
     * @return string the formatted content as HTML paragraphs
     */
    public static function formatRssContent(string $content): string
    {
        $paragraphs = explode("\n", trim($content));

        return array_reduce($paragraphs, static fn ($carry, $paragraph) => $carry.(!empty($paragraph) ? '<p>'.htmlspecialchars($paragraph).'</p>' : ''), '');
    }
}
