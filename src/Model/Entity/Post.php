<?php

/**
 * This file is part of the Weblog.
 *
 * Copyright (c) 2024-2025  René Coignard <contact@renecoignard.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Weblog\Model\Entity;

use Weblog\Config;
use Weblog\Utils\StringUtils;
use Weblog\Model\Enum\Beautify;

final class Post
{
    /**
     * Constructs a new Post instance with specified properties.
     *
     * @param string             $title    the title of the Post
     * @param string             $path     the file path of the Post
     * @param \DateTimeImmutable $date     the last modified date the Post
     * @param bool               $isDraft  indicates if the Post is a draft
     * @param bool               $isHidden indicates if the Post is hidden
     */
    public function __construct(
        private readonly string $title,
        private readonly string $path,
        private readonly \DateTimeImmutable $date,
        private bool $isDraft  = false,
        private bool $isHidden = false
    ) {
        $this->isDraft = $isDraft;
        $this->isHidden = $isHidden;
    }

    /**
     * Checks if the post is a draft.
     *
     * @return bool True if the post is a draft, false otherwise.
     */
    public function isDraft(): bool
    {
        return $this->isDraft;
    }

    /**
     * Checks if the post is hidden.
     *
     * @param bool $checkPath Whether to check the path for hidden directories.
     *
     * @return bool True if the post is hidden, false otherwise.
     */
    public function isHidden(bool $checkPath = true): bool
    {
        return $this->isHidden || ($checkPath && $this->isHiddenPath($this->path));
    }

    /**
     * Determines if any component in the path is hidden.
     *
     * @param string $path The path to check.
     * @return bool Returns true if the path contains hidden components, false otherwise.
     */
    private function isHiddenPath(string $path): bool
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);
        foreach ($pathParts as $part) {
            if (str_starts_with($part, '.')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Creates an instance from a file.
     *
     * This method extracts the title from the file's name, uses the full path as the path,
     * and sets the date based on the file's last modification time.
     *
     * @param \SplFileInfo $file the file from which to create the instance
     * @param bool $isDraft indicates if the file represents a draft
     *
     * @return self returns a Post instance populated with data from the file
     */
    public static function createFromFile(\SplFileInfo $file, bool $isDraft = false): self {
        $date = new \DateTimeImmutable('@'.$file->getMTime());
        $date = $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $isDraft = strpos($file->getPathname(), '/drafts/') !== false;
        $isHidden = str_starts_with(basename($file->getFilename(), '.'), '.');

        return new self(
            title: basename($file->getFilename(), '.txt'),
            path: $file->getPathname(),
            date: $date,
            isDraft: $isDraft,
            isHidden: $isHidden
        );
    }

    public function isSelected(): bool
    {
        return str_starts_with($this->title, '*');
    }

    public function getTitle(): string
    {
        $title = ltrim($this->title, '*.');

        $hideSelected = Config::get()->hideSelected;

        if ($this->isSelected() && !$hideSelected) {
            $title .= Config::get()->beautify === Beautify::OFF ? ' *' : ' ★';
        }

        return $title;
    }

    public function getSlug(): string
    {
        return StringUtils::slugify($this->getTitle());
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getDatetimestamp(): int
    {
        return $this->date->getTimestamp();
    }

    public function getFormattedDate(string $format = 'Y-m-d'): string
    {
        return $this->date->format($format);
    }

    public function getCategory(): string
    {
        $relativePath = str_replace(Config::get()->weblogDir, '', $this->getPath());
        $pathParts = explode('/', trim($relativePath, '/'));

        $category = (\count($pathParts) > 1) ? ucfirst(ltrim($pathParts[0], '.')) : 'Misc';

        return $category;
    }

    public function getContent(): string
    {
        $content = file_get_contents($this->path);

        return false === $content ? '' : $content;
    }
}
