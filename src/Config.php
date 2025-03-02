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

namespace Weblog;

use Weblog\Model\Entity\Author;
use Weblog\Model\Enum\ShowUrls;
use Weblog\Model\Enum\Beautify;
use Weblog\Utils\StringUtils;
use Weblog\Utils\Validator;

final class Config
{
    private const VERSION = '1.18.8';
    private const CONFIG_PATH = __DIR__.'/../config.ini';

    /**
     * @var array<string,string>
     */
    private array $config = [];
    private static ?Config $instance = null;

    /**
     * Private constructor to prevent creating a new instance of the Config singleton.
     */
    private function __construct(
        public Author $author = new Author(),
        public string $version = self::VERSION,
        public string $domain = 'localhost',
        public string $url = 'http://localhost',
        public int $lineWidth = 72,
        public int $prefixLength = 3,
        public string $weblogDir = __DIR__.'/../weblog/',
        public bool $showPoweredBy = true,
        public ShowUrls $showUrls = ShowUrls::OFF,
        public bool $showCategory = true,
        public bool $showDate = true,
        public bool $showCopyright = true,
        public bool $showSeparator = false,
        public bool $capitalizeTitles = false,
        public array $rewrites = [],
        public Beautify $beautify = Beautify::RSS,
        public bool $hideSelected = true,
        public bool $shortenDate = false,
        public bool $enableLogging = false,
        public string $logFilePath = '/var/log/weblog/access.log',
        public array $logFilterWords = [],
        public array $logFilterAgents = [],
        public string $sourceCodeUrl = 'https://github.com/coignard/weblog'
    ) {
        $this->loadConfig();
    }

    /**
     * Returns a new Config instance.
     */
    public static function get(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Loads configuration from a config.ini file.
     */
    private function loadConfig(): void
    {
        if (!file_exists(self::CONFIG_PATH)) {
            throw new \RuntimeException('Configuration file not found.');
        }

        if (!$config = parse_ini_file(self::CONFIG_PATH, true)) {
            throw new \RuntimeException('Failed to parse configuration file.');
        }

        $this->config = $config['Weblog'];

        if (isset($this->config['weblog_dir']) && !is_dir($this->getString('weblog_dir') ?? '')) {
            throw new \RuntimeException('Weblog directory not found.');
        }

        $this->setAuthor();

        $protocol = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'] || 443 === $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';

        $this->lineWidth = $this->getInt('line_width') ?? $this->lineWidth;
        $this->prefixLength = $this->getInt('prefix_length') ?? $this->prefixLength;
        $this->weblogDir = $this->getString('weblog_dir') ?? $this->weblogDir;
        $this->domain = $this->getString('domain') ?? $this->domain;
        $this->url = rtrim($protocol.$this->getString('domain'), '/');
        $this->showPoweredBy = $this->getBool('show_powered_by') ?? $this->showPoweredBy;
        $this->showUrls = ShowUrls::tryFrom(is_bool($this->getString('show_urls')) ? ShowUrls::OFF->value : ($this->getString('show_urls') ?? '')) ?? ShowUrls::OFF;
        $this->showCategory = $this->getBool('show_category') ?? $this->showCategory;
        $this->showDate = $this->getBool('show_date') ?? $this->showDate;
        $this->showCopyright = $this->getBool('show_copyright') ?? $this->showCopyright;
        $this->showSeparator = $this->getBool('show_separator') ?? $this->showSeparator;
        $this->capitalizeTitles = $this->getBool('capitalize_titles') ?? $this->capitalizeTitles;
        $this->beautify = Beautify::tryFrom(is_bool($this->getString('beautify')) ? Beautify::OFF->value : ($this->getString('beautify') ?? '')) ?? Beautify::OFF;
        $this->hideSelected = $this->getBool('hide_selected') ?? $this->hideSelected;
        $this->shortenDate = $this->getBool('shorten_date') ?? $this->shortenDate;
        $this->enableLogging = $this->getBool('enable_logging') ?? $this->enableLogging;
        $this->logFilePath = $this->getString('log_file_path') ?? '/var/log/weblog/access.log';
        $this->logFilterWords = array_map('trim', explode(',', $this->getString('log_filter_words') ?? ''));
        $this->logFilterAgents = array_map('trim', explode(',', $this->getString('log_filter_agents') ?? ''));
        $this->sourceCodeUrl = ($this->getString('source_code_url') ?? '') !== ''
            ? $this->getString('source_code_url')
            : throw new \RuntimeException('To comply with the GNU Affero General Public License (AGPL), please provide the source code URL in the config.ini.');

        $this->rewrites = $config['Rewrites'] ?? $this->rewrites;

        $this->handleMobileDevice();
    }

    private function handleMobileDevice(): void
    {
        if (false === Validator::isMobileDevice()) {
            return;
        }

        $this->lineWidth = (int) ($this->lineWidth / 2) + 6;
        $this->showCategory = false;
        $this->showDate = false;
        $this->showUrls = ShowUrls::OFF;
    }

    private function setAuthor(): void
    {
        $name = $this->getString('author_name') ?? $this->author->getName();
        $email = $this->getString('author_email') ?? $this->author->getEmail();
        $location = $this->getString('author_location') ?? $this->author->getLocation();

        $aboutText = Validator::isMobileDevice() && isset($this->config['about_text_alt'])
            ? $this->getString('about_text_alt')
            : $this->getString('about_text');
        $aboutText = StringUtils::sanitizeText($aboutText ?? $this->author->getAbout());

        $this->author = new Author(
            name: $name,
            email: $email,
            location: $location,
            aboutText: $aboutText
        );
    }

    private function getInt(string $key): ?int
    {
        return isset($this->config[$key]) && is_numeric($this->config[$key]) ? (int) ($this->config[$key]) : null;
    }

    private function getString(string $key): ?string
    {
        return isset($this->config[$key]) ? (string) ($this->config[$key]) : null;
    }

    private function getBool(string $key): ?bool
    {
        return isset($this->config[$key]) ? filter_var($this->config[$key], FILTER_VALIDATE_BOOLEAN) : null;
    }
}
