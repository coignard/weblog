<?php

declare(strict_types=1);

namespace Weblog\Model\Entity;

use Weblog\Config;
use Weblog\Utils\StringUtils;
use Weblog\Model\Enum\Beautify;

final class Author
{
    /**
     * Initializes a new Author with specified details.
     *
     * @param string $name      the name of the author
     * @param string $email     the email address of the author
     * @param string $location  the city or country of the author
     * @param string $aboutText a brief description or bio of the author
     */
    public function __construct(
        private readonly string $name = 'Unknown',
        private readonly string $email = 'no-reply@example.com',
        private readonly string $location = '',
        private string $aboutText = '',
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAbout(): string
    {
        return $this->aboutText;
    }

    public function setAbout(string $aboutText): void
    {
        $this->aboutText = StringUtils::formatAboutText($aboutText);
    }

    public function getInformation(): string
    {
        return $this->email ?? $this->name;
    }

    public function getLocation(): string
    {
        return $this->location;
    }
}
