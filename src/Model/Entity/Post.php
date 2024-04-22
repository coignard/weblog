<?php

declare(strict_types=1);

namespace Weblog\Model\Entity;

use Weblog\Config;
use Weblog\Utils\StringUtils;

final class Post
{
    /**
     * Constructs a new Post instance with specified properties.
     *
     * @param string             $title the title of the Post
     * @param string             $path  the file path of the Post
     * @param \DateTimeImmutable $date  the last modified date the Post
     */
    public function __construct(
        private readonly string $title,
        private readonly string $path,
        private readonly \DateTimeImmutable $date,
    ) {
    }

    /**
     * Creates an instance from a file.
     *
     * This method extracts the title from the file's name, uses the full path as the path,
     * and sets the date based on the file's last modification time.
     *
     * @param \SplFileInfo $file the file from which to create the instance
     *
     * @return self returns a Post instance populated with data from the file
     */
    public static function createFromFile(\SplFileInfo $file): self
    {
        return new self(
            title: basename($file->getFilename(), '.txt'),
            path: $file->getPathname(),
            date: new \DateTimeImmutable('@'.$file->getMTime()),
        );
    }

    public function getTitle(): string
    {
        return $this->title;
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
