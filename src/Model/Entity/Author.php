<?php

/**
 * This file is part of the Weblog.
 *
 * Copyright (c) 2024  René Coignard <contact@renecoignard.com>
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

    public function getInformation(): string
    {
        return $this->email ?? $this->name;
    }

    public function getLocation(): string
    {
        return $this->location;
    }
}
