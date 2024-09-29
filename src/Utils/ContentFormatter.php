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
            $paragraph = preg_replace('/`([^`]*)`/', '$1', $paragraph);

            if (!Validator::isMobileDevice()) {
                $trimmedParagraph = preg_replace('/([.!?]|\.{3})(["\'])?(\s)/', '$1$2 $3', trim($paragraph));
            } else {
                $trimmedParagraph = trim($paragraph);
            }

            if (preg_match('/^(#+)\s*(.*)$/', $trimmedParagraph, $matches)) {
                $text = $matches[2];
                if (!Validator::isMobileDevice()) {
                    $formattedContent .= "\n" . TextUtils::centerText($text) . "\n\n\n";
                } else {
                    $formattedContent .= "\n" . " " . TextUtils::centerText($text) . "\n\n\n";
                }
                continue;
            }

            if (str_starts_with($trimmedParagraph, '>')) {
                $formattedContent .= TextUtils::formatQuote($paragraph) . "\n\n";
            } elseif (preg_match('/^(\d+)\.\s/', $trimmedParagraph, $matches) || preg_match('/^\* /', $trimmedParagraph)) {
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
    public static function formatPostHeader(string $title = '', string $category = '', string $date = ''): string
    {
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

        if (mb_strlen($title) > 32) {
            $titleLines = wordwrap($title, 32, "\n", true);
            $titleParts = explode("\n", $titleLines);
        } else {
            $titleParts = [$title];
        }

        $header = '';

        foreach ($titleParts as $index => $titleLine) {
            $titlePaddingLeft = (int)(($titleWidth - mb_strlen($titleLine)) / 2);
            $titlePaddingRight = $titleWidth - mb_strlen($titleLine) - $titlePaddingLeft;

            if (Validator::isMobileDevice()) {
                $titlePaddingLeft += 1;
            }

            if ($index > 0) {
                $header .= "\n" . str_repeat(' ', $titlePaddingLeft + $categoryWidth) . $titleLine . str_repeat(' ', $titlePaddingRight + $dateWidth);
            } else {
                if ($includeCategory) {
                    $header .= str_pad($category, $categoryWidth);
                }
                $header .= str_repeat(' ', $titlePaddingLeft) . $titleLine . str_repeat(' ', $titlePaddingRight);
                if ($includeDate) {
                    if (Config::get()->shortenDate) {
                        $date = (new \DateTime($date))->format('j M \'y');
                    }
                    $header .= str_pad($date, $dateWidth, ' ', STR_PAD_LEFT);
                }
            }
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
        $listItems = [];

        foreach ($paragraphs as $paragraph) {
            $trimmedParagraph = trim($paragraph);

            if (preg_match('/^(#+)\s*(.*)$/', $trimmedParagraph, $matches)) {
                $level = strlen($matches[1]);
                $heading = htmlspecialchars($matches[2]);
                $tag = 'h' . min($level, 6);

                $formattedContent .= "<{$tag}>" . $heading . "</{$tag}>\n";
                continue;
            }

            if (str_starts_with($trimmedParagraph, '>')) {
                $quoteText = substr($trimmedParagraph, 1);
                if (!$insideBlockquote) {
                    $insideBlockquote = true;
                    $blockquoteContent .= '<blockquote>';
                }
                $blockquoteContent .= ltrim(htmlspecialchars($quoteText)) . '<br />';
            } elseif (preg_match('/^(\d+)\.\s/', $trimmedParagraph, $matches) || preg_match('/^\* /', $trimmedParagraph)) {
                $listType = isset($matches[1]) ? 'ol' : 'ul';

                if (!$insideList) {
                    $insideList = true;
                    $listItems = [];
                }

                $itemText = isset($matches[1]) ? trim(substr($trimmedParagraph, strlen($matches[0]))) : trim(substr($trimmedParagraph, 2));
                $listItems[] = htmlspecialchars($itemText);
            } else {
                if ($insideBlockquote) {
                    $insideBlockquote = false;
                    $blockquoteContent .= '</blockquote>';
                    $formattedContent .= $blockquoteContent;
                    $blockquoteContent = '';
                }

                if ($insideList) {
                    $insideList = false;
                    if (count($listItems) == 1 && $listType === 'ol') {
                        $formattedContent .= '<h2>' . $listItems[0] . '</h2>';
                    } else {
                        $listContent = $listType === 'ol' ? '<ol>' : '<ul>';
                        foreach ($listItems as $item) {
                            $listContent .= '<li>' . $item . '</li>';
                        }
                        $listContent .= $listType === 'ol' ? '</ol>' : '</ul>';
                        $formattedContent .= $listContent;
                    }
                    $listItems = [];
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

        if ($insideList && count($listItems) == 1 && $listType === 'ol') {
            $formattedContent .= '<h2>' . $listItems[0] . '</h2>';
        } elseif ($insideList) {
            $listContent = $listType === 'ol' ? '<ol>' : '<ul>';
            foreach ($listItems as $item) {
                $listContent .= '<li>' . $item . '</li>';
            }
            $listContent .= $listType === 'ol' ? '</ol>' : '</ul>';
            $formattedContent .= $listContent;
        }

        $formattedContent = str_replace('<br /></blockquote>', '</blockquote>', $formattedContent);

        return $formattedContent;
    }
}
