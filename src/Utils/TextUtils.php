<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;

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

        return str_repeat(' ', (int) floor($leftPadding)).$text;
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
        $words = explode(' ', $text);
        $line = $linePrefix;
        $result = '';

        foreach ($words as $word) {
            if (mb_strlen($line.$word) > $lineWidth) {
                $result .= rtrim($line)."\n";
                $line = $linePrefix.$word.' ';
            } else {
                $line .= $word.' ';
            }
        }

        return $result.rtrim($line);
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

        $leftText = '';
        $centerText = Config::get()->author->getName();
        $rightText = '';

        $leftWidth = mb_strlen($leftText);
        $centerWidth = mb_strlen($centerText);
        $rightWidth = mb_strlen($rightText);

        $spaceToLeft = (int) (($lineWidth - $centerWidth) / 2);
        $spaceToRight = $lineWidth - $spaceToLeft - $centerWidth;

        if (Validator::isMobileDevice() && ($centerWidth % 2) !== 0) {
            $spaceToLeft += 2;
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
        $paragraphs = explode("\n", Config::get()->author->getAbout());
        $formattedAboutText = '';

        foreach ($paragraphs as $paragraph) {
            $formattedParagraph = $paragraph;
            if (!Validator::isMobileDevice()) {
                $formattedParagraph = preg_replace('/([.!?]|\.{3})(\s)/', '$1 $2', rtrim($paragraph));
            }
            $formattedAboutText .= self::formatParagraph($formattedParagraph ?? '')."\n";
        }

        if (Config::get()->showSeparator) {
            $separator = "\n\n\n".str_repeat(' ',
                Validator::isMobileDevice() ? Config::get()->prefixLength : 0).
            str_repeat('_', Config::get()->lineWidth - (Validator::isMobileDevice() ? Config::get()->prefixLength : 0))."\n\n\n\n\n";
            $formattedAboutText .= $separator;
        } else {
            $formattedAboutText .= "\n\n\n\n";
        }

        return $formattedAboutText;
    }
}
