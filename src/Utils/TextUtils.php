<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;
use Weblog\Model\Enum\Beautify;

final class TextUtils
{
    /**
     * Centers text within the configured line width.
     *
     * @param string $text the text to be centered
     *
     * @return string the centered text
     */
    public static function centerText(string $text): string
    {
        $lineWidth = Config::get()->lineWidth;
        $leftPadding = ($lineWidth - mb_strlen($text)) / 2;

        if ($leftPadding < 0) {
            return $text;
        }

        return str_repeat(' ', (int) floor($leftPadding)).$text;
    }

    /**
     * Formats a quote block.
     *
     * @param string $text The text to be formatted as a quote.
     *
     * @return string The formatted quote.
     */
    public static function formatQuote(string $text): string
    {
        $lines = explode("\n", $text);
        $formattedText = '';
        $insideQuote = false;
        $quoteContent = '';
        $maxWidth = Validator::isMobileDevice() ? 30 : 56;
        $isSingleQuote = false;

        foreach ($lines as $line) {
            $trimmedLine = ltrim($line);

            if (str_starts_with($trimmedLine, '>')) {
                if (!$insideQuote) {
                    $insideQuote = true;
                    $quoteContent .= ltrim(substr($trimmedLine, 1));
                } else {
                    $quoteContent .= "\n" . ltrim(substr($trimmedLine, 1));
                }
            } else {
                if ($insideQuote) {
                    $insideQuote = false;

                    $quoteLines = explode("\n", trim($quoteContent));
                    if (count($quoteLines) === 1) {
                        $singleQuote = trim($quoteLines[0]);
                        $isSingleQuote = true;
                        if (mb_strlen($singleQuote) > $maxWidth) {
                            $wrappedLines = explode("\n", wordwrap($singleQuote, $maxWidth));
                            $centeredQuote = "";
                            foreach ($wrappedLines as $wrappedLine) {
                                if (!Validator::isMobileDevice()) {
                                    $centeredQuote .= TextUtils::centerText($wrappedLine) . "\n";
                                } else {
                                    $centeredQuote .= " " . TextUtils::centerText($wrappedLine) . "\n";
                                }
                            }
                            $quoteContent = $centeredQuote;
                        } else {
                            $quoteContent = TextUtils::centerText($singleQuote);
                        }
                    } else {
                        if (!Validator::isMobileDevice()) {
                            $quoteContent = self::formatQuoteText($quoteContent);
                        } else {
                            $quoteContent = " " . self::formatQuoteText($quoteContent);
                        }
                    }

                    $formattedText .= "\n" . $quoteContent . "\n";
                    $quoteContent = '';
                }
                $formattedText .= self::formatParagraph($line) . "\n";
            }
        }

        if ($insideQuote) {
            $quoteLines = explode("\n", trim($quoteContent));
            if (count($quoteLines) === 1) {
                $singleQuote = trim($quoteLines[0]);
                $isSingleQuote = true;
                if (mb_strlen($singleQuote) > $maxWidth) {
                    $wrappedLines = explode("\n", wordwrap($singleQuote, $maxWidth));
                    $centeredQuote = "";
                    foreach ($wrappedLines as $wrappedLine) {
                        if (!Validator::isMobileDevice()) {
                            $centeredQuote .= TextUtils::centerText($wrappedLine) . "\n";
                        } else {
	                    $centeredQuote .= " " . TextUtils::centerText($wrappedLine) . "\n";
                        }
                    }
                    $quoteContent = $centeredQuote;
                } else {
                    if (!Validator::isMobileDevice()) {
                        $quoteContent = TextUtils::centerText($singleQuote);
                    } else {
                        $quoteContent = " " . TextUtils::centerText($singleQuote);
                    }
                }
            } else {
                $quoteContent = self::formatQuoteText($quoteContent);
            }

            $formattedText .= $quoteContent;
        }

        if ($isSingleQuote) {
            return "\n" . rtrim($formattedText) . "\n";
        } else {
            return rtrim($formattedText);
        }
    }

    /**
     * Formats the text of a quote block.
     *
     * @param string $text The raw text of the quote.
     *
     * @return string The formatted quote text.
     */
    public static function formatQuoteText(string $text): string
    {
        $lineWidth = Config::get()->lineWidth;
        $prefix = str_repeat(' ', Config::get()->prefixLength) . '|  ';
        $lines = explode("\n", wordwrap(trim($text), $lineWidth - Config::get()->prefixLength - 4));

        $formattedText = '';
        foreach ($lines as $line) {
            $formattedText .= $prefix . $line . "\n";
        }

        return rtrim($formattedText);
    }

    /**
     * Formats a list block.
     *
     * @param string $text The text to be formatted as a list.
     *
     * @return string The formatted list.
     */
    public static function formatList(string $text): string
    {
        $lines = explode("\n", $text);
        $formattedText = '';
        $insideList = false;
        $listContent = '';
        $listType = '';

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if (preg_match('/^(\d+)\.\s/', $trimmedLine, $matches)) {
                if ($listType === 'ul') {
                    $formattedText .= $listContent . "\n";
                    $listContent = '';
                    $listType = '';
                }

                $listType = 'ol';
                if (!$insideList) {
                    $insideList = true;
                    $listContent .= self::formatListItem($line, $listType, (int)$matches[1]);
                } else {
                    $listContent .= ($insideList && !empty($listContent) ? "\n\n" : "") . self::formatListItem($line, $listType, (int)$matches[1]);
                }
            } elseif (preg_match('/^[-*] /', $trimmedLine)) {
                if ($listType === 'ol') {
                    $formattedText .= $listContent . "\n";
                    $listContent = '';
                    $listType = '';
                }

                $listType = 'ul';
                if (!$insideList) {
                    $insideList = true;
                    $listContent .= self::formatListItem($line, $listType);
                } else {
                    $listContent .= ($insideList && !empty($listContent) ? "\n\n" : "") . self::formatListItem($line, $listType);
                }
            } else {
                if ($insideList) {
                    $listContent .= "\n" . self::formatListItem($line, $listType, 0, true);
                } else {
                    $formattedText .= TextUtils::formatParagraph($trimmedLine) . "\n";
                }
            }
        }

        if ($insideList) {
            $formattedText .= $listContent;
        }

        return rtrim($formattedText);
    }

    /**
     * Formats a list item.
     *
     * @param string $item The text of the list item.
     * @param string $listType The type of the list ('ol' for ordered, 'ul' for unordered).
     * @param int $index The index of the list item (only for ordered lists).
     *
     * @return string The formatted list item.
     */
    public static function formatListItem(string $item, string $listType, int $index = 1, bool $isContinuation = false): string
    {
        $lineWidth = Config::get()->lineWidth;
        $prefixLength = Config::get()->prefixLength;
        $linePrefix = str_repeat(' ', $prefixLength);

        if ($listType === 'ol' && !$isContinuation) {
            $number = $index . '.';
            $suffix = (strlen((string)$index) === 1) ? '  ' : ' ';
            $linePrefix .= $number . $suffix;
            $itemText = trim(substr($item, strlen($number)));
        } elseif ($listType === 'ul' && !$isContinuation) {
            $bullet = in_array(Config::get()->beautify, [Beautify::ALL, Beautify::CONTENT]) ? '•' : '*';
            $linePrefix .= $bullet . '  ';
            $itemText = trim(substr($item, 2));
        } else {
            $linePrefix .= ' ';
            $itemText = $item;
        }

        $words = explode(' ', $itemText);
        $line = $linePrefix;
        $result = '';

        foreach ($words as $word) {
            if (mb_strlen($line . $word) > $lineWidth) {
                $result .= rtrim($line) . "\n";
                $line = str_repeat(' ', mb_strlen($linePrefix)) . $word . ' ';
            } else {
                $line .= $word . ' ';
            }
        }

        return $result . rtrim($line);
    }

    /**
     * Formats a paragraph to fit within the configured line width, using a specified prefix length.
     *
     * @param string $text the text of the paragraph
     *
     * @return string the formatted paragraph
     */
    public static function formatParagraph(string $text): string
    {
        $lineWidth = Config::get()->lineWidth;
        $prefixLength = Config::get()->prefixLength;
        $linePrefix = str_repeat(' ', $prefixLength);

        $result = '';

        $words = explode(' ', $text);
        $line = $linePrefix;

        foreach ($words as $word) {
            if (mb_strlen($word) > $lineWidth - $prefixLength) {
                if (mb_strlen($line) > $prefixLength) {
                    $result .= rtrim($line) . "\n";
                    $line = $linePrefix;
                }
                while (mb_strlen($word) > $lineWidth - $prefixLength) {
                    $result .= $linePrefix . mb_substr($word, 0, $lineWidth - $prefixLength) . "\n";
                    $word = mb_substr($word, $lineWidth - $prefixLength);
                }
                $line .= $word . ' ';
            } else {
                if (mb_strlen($line . $word) > $lineWidth) {
                    $result .= rtrim($line) . "\n";
                    $line = $linePrefix . $word . ' ';
                } else {
                    $line .= $word . ' ';
                }
            }
        }

        $result .= rtrim($line);

        $result = preg_replace('/\.\s*\n\s+/', ".\n" . $linePrefix, $result);

        return $result;
    }

    /**
     * Formats a string with legal information.
     *
     * @return string the formatted paragraph
     */
    public static function formatCopyrightText(string $dateRange): string
    {
        $authorInfo = Config::get()->author->getInformation();

        return "Copyright (c) {$dateRange} {$authorInfo}";
    }

    /**
     * Formats the About section header with "About" on the left and the author's name centered.
     *
     * @return string the formatted header string
     */
    public static function formatAboutHeader(): string
    {
        $lineWidth = Config::get()->lineWidth;

        $leftText = Validator::isMobileDevice() ? '' : 'About';
        $centerText = Config::get()->author->getName();
        $rightText = Validator::isMobileDevice() ? '' : Config::get()->author->getCity();

        $leftText = StringUtils::capitalizeText($leftText);
        $centerText = StringUtils::capitalizeText($centerText);
        $rightText = StringUtils::capitalizeText($rightText);

        $leftWidth = mb_strlen($leftText);
        $centerWidth = mb_strlen($centerText);
        $rightWidth = mb_strlen($rightText);

        $spaceToLeft = (int) (($lineWidth - $centerWidth) / 2);
        $spaceToRight = $lineWidth - $spaceToLeft - $centerWidth;

        if (Validator::isMobileDevice()) {
            $spaceToLeft += 1;
        }

        return "\n\n\n\n".sprintf(
            '%s%s%s%s%s',
            $leftText,
            str_repeat(' ', $spaceToLeft - $leftWidth),
            $centerText,
            str_repeat(' ', $spaceToRight - $rightWidth),
            $rightText
        )."\n\n\n";
    }

    /**
     * Formats a paragraph from the about text.
     *
     * @return string the formatted paragraph
     */
    public static function formatAboutText(): string
    {
        $aboutText = Config::get()->author->getAbout();

        if (in_array(Config::get()->beautify, [Beautify::ALL, Beautify::CONTENT])) {
            $aboutText = StringUtils::beautifyText($aboutText);
        }

        $paragraphs = explode("\n", $aboutText);

        $formattedAboutText = '';

        foreach ($paragraphs as $paragraph) {
            $formattedParagraph = $paragraph;
            if (!Validator::isMobileDevice()) {
                $formattedParagraph = preg_replace('/([.!?]|\.{3})(\s)/', '$1 $2', rtrim($paragraph));
            }
            $formattedAboutText .= self::formatParagraph($formattedParagraph ?? '')."\n";
        }

        if (Config::get()->showSeparator) {
            $separator = "\n\n\n".str_repeat(
                ' ',
                Validator::isMobileDevice() ? Config::get()->prefixLength : 0
            ).
            str_repeat('—', Config::get()->lineWidth - (Validator::isMobileDevice() ? Config::get()->prefixLength : 0))."\n\n\n\n\n";
            $formattedAboutText .= $separator;
        } else {
            $formattedAboutText .= "\n\n\n\n";
        }

        return $formattedAboutText;
    }
}
