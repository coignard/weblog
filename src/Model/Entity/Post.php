<?php

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
     * @param string             $title   the title of the Post
     * @param string             $path    the file path of the Post
     * @param \DateTimeImmutable $date    the last modified date the Post
     * @param bool               $isDraft indicates if the Post is a draft
     */
    public function __construct(
        private readonly string $title,
        private readonly string $path,
        private readonly \DateTimeImmutable $date,
        private bool $isDraft = false
    ) {
        $this->isDraft = $isDraft;
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
        return new self(
            title: basename($file->getFilename(), '.txt'),
            path: $file->getPathname(),
            date: $date,
            isDraft: $isDraft
        );
    }

    public function isSelected(): bool
    {
        return str_starts_with($this->title, '*');
    }

    public function getTitle(): string
    {
        $title = ltrim($this->title, '*');
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

        return (\count($pathParts) > 1) ? ucfirst($pathParts[0]) : 'Misc';
    }

    public function getContent(): string
    {
        $content = file_get_contents($this->path);

        return false === $content ? '' : $content;
    }
}
