<?php

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
    public function handleNotFound(string $message = "404 Not Found\n"): void
    {
        http_response_code(404);

        if (1 === random_int(1, 10)) {
            echo "404 Cat Found\n\n  ／l、meow\n（ﾟ､ ｡ ７\n  l  ~ヽ\n  じしf_,)ノ\n";

            return;
        }

        echo $message;
    }

    /**
     * Renders the footer with dynamic copyright information based on the post dates or a specific year if provided.
     *
     * @param string|null $year the specific year for the post page or null.
     */
    public function renderFooter(?string $year = null): void
    {
        echo Config::get()->showPoweredBy ? "\n\n\n\n" : "\n\n\n";
        
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
     * @param ContentType $contentType enum.
     */
    public function setHeaders(ContentType $contentType): void
    {
        header(sprintf('Content-Type: %s; charset=utf-8', $contentType->value));
    }
}
