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
        $text = preg_replace('/([.!?]|\.{3})(["\'])?(\s)/', '$1$2 $3', $text);

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

        $formattedText = preg_replace(
            '/[\x{202F}\x{00A0}]/u',
            ' ',
            $formattedText
        );


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

        $formattedText = preg_replace(
            '/[\x{202F}\x{00A0}]/u',
            ' ',
            $formattedText
        );

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
        $text = preg_replace('/([.!?]|\.{3})(["\'])?(\s)/', '$1$2 $3', $text);

        $lines = explode("\n", $text);
        $formattedText = '';
        $insideList = false;
        $listContent = '';
        $listType = '';
        $listItems = [];
        $listCount = 0;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if (preg_match('/^(\d+)\.\s/', $trimmedLine, $matches)) {
                $listCount++;
            } elseif (preg_match('/^\* /', $trimmedLine)) {
                $listCount++;
            }
        }

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if (preg_match('/^(\d+)\.\s/', $trimmedLine, $matches)) {
                if ($listType === 'ul') {
                    $formattedText .= $listContent . "\n";
                    $listContent = '';
                    $listType = '';
                }

                $listType = 'ol';
                $index = (int)$matches[1];
                if (!$insideList) {
                    $insideList = true;
                    $listContent .= self::formatListItem($line, $listType, $index, $listCount);
                } else {
                    $listContent .= ($insideList && !empty($listContent) ? "\n\n" : "") . self::formatListItem($line, $listType, $index, $listCount);
                }
            } elseif (preg_match('/^\* /', $trimmedLine)) {
                if ($listType === 'ol') {
                    $formattedText .= $listContent . "\n";
                    $listContent = '';
                    $listType = '';
                }

                $listType = 'ul';
                if (!$insideList) {
                    $insideList = true;
                    $listContent .= self::formatListItem($line, $listType, 0, $listCount);
                } else {
                    $listContent .= ($insideList && !empty($listContent) ? "\n\n" : "") . self::formatListItem($line, $listType, 0, $listCount);
                }
            } else {
                if ($insideList) {
                    $listContent .= "\n" . self::formatListItem($line, $listType, 0, $listCount, true);
                } else {
                    $formattedText .= TextUtils::formatParagraph($trimmedLine) . "\n";
                }
            }
        }

        if ($insideList) {
            $formattedText .= $listContent;
        }

        $formattedText = preg_replace(
            '/[\x{202F}\x{00A0}]/u',
            ' ',
            $formattedText
        );

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
    public static function formatListItem(string $item, string $listType, int $index = 1, int $totalItems = 10, bool $isContinuation = false): string
    {
        $lineWidth = Config::get()->lineWidth;
        $prefixLength = Config::get()->prefixLength;
        $linePrefix = str_repeat(' ', $prefixLength);

        $maxDigits = strlen((string)$totalItems);
        $indexDigits = strlen((string)$index);

        if ($listType === 'ol' && !$isContinuation) {
            $number = $index . '.';
            $suffix = str_repeat(' ', $maxDigits - $indexDigits + 2);
            $linePrefix .= $number . $suffix;
            $itemText = trim(substr($item, strlen($number)));
        } elseif ($listType === 'ul' && !$isContinuation) {
            $isBeautifyEnabled = in_array(Config::get()->beautify, [Beautify::ALL, Beautify::CONTENT]);
            $bullet = $item[0] === '*' ? ($isBeautifyEnabled ? '•' : '*') : ($isBeautifyEnabled ? '—' : '-');
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

        $result = preg_replace(
            '/[\x{202F}\x{00A0}]/u',
            ' ',
            $result
        );

        return $result . rtrim($line);
    }

    /**
     * Formats asterism text.
     *
     * @param string $text the text to be formatted
     *
     * @return string the formatted text
     */
    public static function formatAsterism(string $text): string
    {
        if (in_array(Config::get()->beautify, [Beautify::ALL, Beautify::CONTENT])) {
            if ($text === '***' || $text === '* * *') {
                return "\n" . self::centerText('⁂') . "\n";
            }
        }

        if ($text === '***') {
            return "\n" . self::centerText('* * *') . "\n";
        }

        if ($text === '* * *') {
            return "\n" . self::centerText('* * *') . "\n";
        }

        return self::centerText($text);
    }

    /**
     * Formats a separator line.
     *
     * @return string the formatted separator
     */
    public static function formatSeparator(): string
    {
        $lineWidth = Config::get()->lineWidth;
        $prefixLength = Config::get()->prefixLength;
        $separator = str_repeat('—', 5);

        return "\n" . self::centerText(str_repeat(' ', $prefixLength) . $separator . str_repeat(' ', $prefixLength)) . "\n";
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
        $text = preg_replace(
            '/\x{202F}/u',
            '\x{00A0}',
            $text
        );

        if (in_array($text, ['***', '* * *'])) {
            return self::formatAsterism($text);
        }

        if ($text === '---') {
            return self::formatSeparator();
        }

        $lineWidth = Config::get()->lineWidth;
        $prefixLength = Config::get()->prefixLength;
        $linePrefix = str_repeat(' ', $prefixLength);

        $result = '';

        $breakingSpaces = '[' .
            '\x{0009}-\x{000D}' .
            '\x{0020}' .
            '\x{1680}' .
            '\x{180E}' .
            '\x{2000}-\x{200A}' .
            '\x{2028}' .
            '\x{2029}' .
            '\x{205F}' .
            '\x{3000}' .
        ']+';

        $tokens = preg_split('/(' . $breakingSpaces . ')/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $line = $linePrefix;

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            $tokenLength = mb_strlen($token);

            if (mb_strlen($line) + $tokenLength > $lineWidth) {
                if (strpos($token, '-') !== false && mb_strlen(trim($token)) <= $lineWidth - mb_strlen($linePrefix)) {
                    $hyphenPos = mb_strpos($token, '-');
                    $firstPart = mb_substr($token, 0, $hyphenPos + 1);
                    $remainingPart = mb_substr($token, $hyphenPos + 1);

                    if (mb_strlen($line) + mb_strlen($firstPart) <= $lineWidth) {
                        $line .= $firstPart;
                        $result .= rtrim($line) . "\n";
                        $line = $linePrefix . $remainingPart;
                    } else {
                        $result .= rtrim($line) . "\n";
                        $line = $linePrefix . $token;
                    }
                } elseif (mb_strlen(trim($token)) > $lineWidth - mb_strlen($linePrefix)) {
                    $result .= rtrim($line) . "\n";
                    $line = $linePrefix;

                    $token = ltrim($token);
                    while (mb_strlen($token) > 0) {
                        $spaceLeft = $lineWidth - mb_strlen($line);
                        $part = mb_substr($token, 0, $spaceLeft);
                        $token = mb_substr($token, $spaceLeft);

                        $line .= $part;

                        if (mb_strlen($token) > 0) {
                            $result .= rtrim($line) . "\n";
                            $line = $linePrefix;
                        }
                    }
                } else {
                    $result .= rtrim($line) . "\n";
                    $line = $linePrefix . ltrim($token);
                }
            } else {
                $line .= $token;
            }
        }

        $result .= rtrim($line);

        $result = preg_replace(
            '/[\x{202F}\x{00A0}]/u',
            ' ',
            $result
        );

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
        $rightText = Validator::isMobileDevice() ? '' : Config::get()->author->getLocation();

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
                $formattedParagraph = preg_replace('/([.!?]|\.{3})(["\'])?(\s)/', '$1$2 $3', rtrim($paragraph));
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
            $formattedAboutText .= "\n\n\n\n\n";
        }

        return $formattedAboutText;
    }
}
