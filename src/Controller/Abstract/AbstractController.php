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

namespace Weblog\Controller\Abstract;

use Weblog\Config;
use Weblog\Model\Enum\ContentType;
use Weblog\Model\PostRepository;
use Weblog\Utils\TextUtils;

abstract class AbstractController
{
    public function __construct(
        protected readonly PostRepository $postRepository,
        protected ContentType $contentType = ContentType::TEXT,
    ) {
        $this->setHeaders($contentType);
    }

    /**
     * Handles the "Not Found" response with a randomized easter egg.
     */
    public function handleNotFound(): void
    {
        http_response_code(404);

        if (1 === random_int(1, 10)) {
            echo <<<EOT
404 Cat Found

  ／l、meow
（ﾟ､ ｡ ７
  l  ~ヽ
  じしf_,)ノ

EOT;

            return;
        }

        echo "404 Not Found\n";

        return;
    }

    /**
     * Renders the footer with dynamic copyright information based on the post dates or a specific year if provided.
     *
     * @param null|string $year the specific year for the post page or null
     */
    public function renderFooter(?string $year = null): void
    {
        echo Config::get()->showPoweredBy ? "\n\n\n\n" : "\n\n\n";

        if (false === Config::get()->showCopyright) {
            return;
        }

        if (null === $year) {
            $dateRange = $this->postRepository->getPostYearsRange();
            $copyrightText = TextUtils::formatCopyrightText($dateRange);
        } else {
            $copyrightText = sprintf('Copyright (c) %s %s', $year, Config::get()->author->getInformation());
        }

        echo TextUtils::centerText($copyrightText);

        if (Config::get()->showPoweredBy) {
            echo "\n\n";
            $poweredByText = 'Powered by Weblog v'.Config::get()->version;
            echo TextUtils::centerText($poweredByText);
        }

        echo "\n\n\n";
    }

    /**
     * Sets the content type header.
     *
     * @param ContentType $contentType enum
     */
    public function setHeaders(ContentType $contentType): void
    {
        header(sprintf('Content-Type: %s; charset=utf-8', $contentType->value));
        header(sprintf('X-Source-Code: %s', Config::get()->sourceCodeUrl));
    }
}
