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
        $paragraphs = preg_split('/\n\s*\n/', trim($content));
        $formattedContent = '';

        foreach ($paragraphs as $paragraph) {
            if (!Validator::isMobileDevice()) {
                $trimmedParagraph = preg_replace('/([.!?]|\.{3})(\s)/', '$1 $2', trim($paragraph));
            } else {
                $trimmedParagraph = trim($paragraph);
            }

            if (str_starts_with($trimmedParagraph, '>')) {
                $formattedContent .= TextUtils::formatQuote($paragraph) . "\n\n";
            } elseif (preg_match('/^(\d+)\.\s/', $trimmedParagraph, $matches) || preg_match('/^- /', $trimmedParagraph)) {
                $formattedContent .= TextUtils::formatList($paragraph) . "\n\n";
            } else {
                $formattedContent .= TextUtils::formatParagraph($trimmedParagraph) . "\n\n";
            }
        }

       	if (in_array(Config::get()->beautify, [Beautify::ALL, Beautify::CONTENT])) {
            $formattedContent = StringUtils::beautifyText($formattedContent);
       	}

        return rtrim($formattedContent) . "\n\n";
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

        if (Validator::isMobileDevice()) {
            $titlePaddingLeft += 1;
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
        $formattedContent = '';
        $insideBlockquote = false;
        $blockquoteContent = '';
        $insideList = false;
        $listContent = '';
        $listType = '';

        foreach ($paragraphs as $paragraph) {
            $trimmedParagraph = trim($paragraph);

            if (str_starts_with($trimmedParagraph, '>')) {
                $quoteText = substr($trimmedParagraph, 1);
                if (!$insideBlockquote) {
                    $insideBlockquote = true;
                    $blockquoteContent .= '<blockquote>';
                }
                $blockquoteContent .= htmlspecialchars($quoteText) . '<br />';
            }
            elseif (preg_match('/^(\d+)\.\s/', $trimmedParagraph, $matches) || preg_match('/^- /', $trimmedParagraph)) {
                $listType = isset($matches[1]) ? 'ol' : 'ul';

                if (!$insideList) {
                    $insideList = true;
                    $listContent .= $listType === 'ol' ? '<ol>' : '<ul>';
                }

                $listContent .= $listType === 'ol' ? '<li>' . htmlspecialchars(trim(substr($trimmedParagraph, strlen($matches[0])))) . '</li>' : '<li>' . htmlspecialchars(trim(substr($trimmedParagraph, 2))) . '</li>';
            }
            else {
                if ($insideBlockquote) {
                    $insideBlockquote = false;
                    $blockquoteContent .= '</blockquote>';
                    $formattedContent .= $blockquoteContent;
                    $blockquoteContent = '';
                }

                if ($insideList) {
                    $insideList = false;
                    $listContent .= $listType === 'ol' ? '</ol>' : '</ul>';
                    $formattedContent .= $listContent;
                    $listContent = '';
                }

                if (!empty($trimmedParagraph)) {
                    $formattedContent .= '<p>' . htmlspecialchars($trimmedParagraph) . '</p>';
                }
            }
        }

        if ($insideBlockquote) {
            $blockquoteContent .= '</blockquote>';
            $formattedContent .= $blockquoteContent;
        }

        if ($insideList) {
            $listContent .= $listType === 'ol' ? '</ol>' : '</ul>';
            $formattedContent .= $listContent;
        }

        return $formattedContent;
    }
}
