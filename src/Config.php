<?php

declare(strict_types=1);

namespace Weblog;

use Weblog\Model\Entity\Author;
use Weblog\Model\Enum\ShowUrls;
use Weblog\Utils\Validator;

final class Config
{
    /**
     * @var array<string,string>
     */
    private array $config = [];
    private static ?Config $instance = null;
    private const VERSION = '1.8.0';
    private const CONFIG_PATH = __DIR__.'/../config.ini';

    /**
     * Private constructor to prevent creating a new instance of the Config singleton.
     */
    private function __construct(
        public Author $author = new Author(),
        public string $version = self::VERSION,
        public string $domain = 'localhost',
        public string $url = 'https://localhost',
        public int $lineWidth = 72,
        public int $prefixLength = 3,
        public string $weblogDir = __DIR__.'/../weblog/',
        public bool $showPoweredBy = true,
        public ShowUrls $showUrls = ShowUrls::FULL,
        public bool $showCategory = true,
        public bool $showDate = true,
        public bool $showCopyright = true,
        public bool $showSeparator = false,
        public array $rewrites = [],
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

        if (!is_dir($this->getString('weblog_dir'))) {
            throw new \RuntimeException('Weblog directory not found.');
        }
        
        $this->setAuthor();

        $protocol = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'] || 443 === $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';

        $this->lineWidth = $this->getInt('line_width');
        $this->prefixLength = $this->getInt('prefix_length');
        $this->weblogDir = $this->getString('weblog_dir');
        $this->domain = $this->getString('domain');
        $this->url = rtrim($protocol.$this->getString('domain'), '/');
        $this->showPoweredBy = $this->getBool('show_powered_by');
        $this->showUrls = ShowUrls::tryFrom($this->getString('show_urls')) ?? ShowUrls::FULL;
        $this->showCategory = $this->getBool('show_category');
        $this->showDate = $this->getBool('show_date');
        $this->showCopyright = $this->getBool('show_copyright');
        $this->showSeparator = $this->getBool('show_separator');

        $this->rewrites = (array) $config['Rewrites'];

        $this->handleMobileDevice();
    }

    private function handleMobileDevice(): void
    {
        if (false === Validator::isMobileDevice()) {
            return;
        }

        $this->lineWidth = (int) ($this->lineWidth / 2) - 1;
        $this->showCategory = false;
        $this->showDate = false;
        $this->showCopyright = false;
        $this->showUrls = ShowUrls::OFF;

        if (isset($this->config['about_text_alt'])) {
            $this->author->setAbout($this->getString($this->config['about_text_alt']));
        }
    }

    private function setAuthor(): void
    {
        $name = $this->getString('author_name');
        $email = $this->getString('author_email');
        $aboutText = $this->getString('about_text');

        if ('' === $name || '' === $email) {
            throw new \InvalidArgumentException('Required author information is missing or invalid.');
        }

        $this->author = new Author(
            name: $name,
            email: $email,
            aboutText: $aboutText
        );
    }

    private function getInt(string $key): int
    {
        return isset($this->config[$key]) && is_numeric($this->config[$key]) ? (int) ($this->config[$key]) : throw new \InvalidArgumentException('Cannot cast value to int.');
    }

    private function getString(string $key): string
    {
        return isset($this->config[$key]) ? (string) ($this->config[$key]) : throw new \InvalidArgumentException('Cannot cast value to string.');
    }

    private function getBool(string $key): bool
    {
        return isset($this->config[$key]) ? filter_var($this->config[$key], \FILTER_VALIDATE_BOOLEAN) : throw new \InvalidArgumentException('Cannot cast value to boolean.');
    }
}
