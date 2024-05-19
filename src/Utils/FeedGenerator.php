<?php

declare(strict_types=1);

namespace Weblog\Utils;

use Weblog\Config;
use Weblog\Model\Entity\Post;
use Weblog\Model\PostCollection;

final class FeedGenerator
{
    /**
     * Creates an XML sitemap from a list of posts.
     *
     * This method takes an array of Post objects and a domain string to construct
     * an XML sitemap compliant with the sitemap protocol. The sitemap lists all posts,
     * sorting them from the most recent based on their dates, and includes the main page.
     *
     * @param PostCollection $posts a collection of Post objects to be included in the sitemap
     *
     * @return \SimpleXMLElement the XML element of the generated sitemap
     */
    public static function generateSiteMap(PostCollection $posts): \SimpleXMLElement
    {
        $sitemap = new \SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        $lastmodDate = $posts->getMostRecentDate() ?? new \DateTimeImmutable();
        self::appendXmlElement($sitemap, 'url', null, [], [
            'loc' => Config::get()->url.'/',
            'lastmod' => $lastmodDate->format('Y-m-d'),
            'priority' => '1.0',
            'changefreq' => 'daily',
        ]);

        foreach ($posts as $post) {
            if (!$post instanceof Post) {
                continue;
            }
            self::appendXmlElement($sitemap, 'url', null, [], [
                'loc' => Config::get()->url.'/'.StringUtils::slugify($post->getTitle()).'/',
                'lastmod' => $post->getFormattedDate(),
                'priority' => '1.0',
                'changefreq' => 'weekly',
            ]);
        }

        return $sitemap;
    }

    /**
     * Generates an RSS feed for a collection of posts.
     *
     * @param PostCollection $posts    the collection of posts to be included in the feed
     * @param string         $category the of posts
     *
     * @return \SimpleXMLElement the RSS feed
     */
    public static function generateRSS(PostCollection $posts, string $category = ''): \SimpleXMLElement
    {
        $rss = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"></rss>');
        $channel = $rss->addChild('channel');
        $lastmodDate = $posts->getMostRecentDate() ?? new \DateTimeImmutable();
        $titleSuffix = '' !== $category ? ' - '.ucfirst($category) : $category;

        self::appendXmlElement($channel, 'title', Config::get()->author->getName().$titleSuffix);
        self::appendXmlElement($channel, 'link', Config::get()->url.'/');
        self::appendAtomLink($channel, Config::get()->url.'/rss/');
       	self::appendXmlElement($channel, 'description', preg_split('/\r\n|\r|\n/', Config::get()->author->getAbout())[0] ?? '');
        self::appendXmlElement($channel, 'language', 'en');
        self::appendXmlElement($channel, 'generator', 'Weblog v'.Config::get()->version);
        self::appendXmlElement($channel, 'lastBuildDate', $lastmodDate->format(DATE_RSS));

        self::appendPostItems($posts, $channel);

        return $rss;
    }

    /**
     * Appends an XML element with attributes to a parent XML element.
     *
     * @param \SimpleXMLElement $parent      the parent XML element
     * @param string            $name        the tag name of the child element
     * @param null|string       $value       the value of the child element
     * @param array             $attributes  an associative array of attributes for the child element
     * @param array             $subelements an associative array of subelements
     */
    private static function appendXmlElement(
        \SimpleXMLElement $parent,
        string $name,
        ?string $value = null,
        array $attributes = [],
        array $subelements = [],
    ): void {
        if (null !== $value || !empty($subelements)) {
            $element = $parent->addChild($name, $value);
            foreach ($attributes as $key => $val) {
                $element->addAttribute($key, $val);
            }
            foreach ($subelements as $subName => $subValue) {
                if (!empty($subValue)) {
                    $element->addChild($subName, $subValue);
                }
            }
        }
    }

    /**
     * Appends an Atom link to the channel element.
     *
     * @param \SimpleXMLElement $channel the parent channel XML element
     * @param string            $href    the href attribute for the Atom link
     */
    private static function appendAtomLink(\SimpleXMLElement $channel, string $href): void
    {
        $atomLink = $channel->addChild('link', null, 'http://www.w3.org/2005/Atom');
        $atomLink->addAttribute('href', $href);
        $atomLink->addAttribute('rel', 'self');
        $atomLink->addAttribute('type', 'application/rss+xml');
    }

    /**
     * Adds post items to the RSS channel.
     *
     * @param PostCollection    $posts   collection of posts to be included
     * @param \SimpleXMLElement $channel the channel XML element
     */
    private static function appendPostItems(PostCollection $posts, \SimpleXMLElement $channel): void
    {
        foreach ($posts as $post) {
            if (!$post instanceof Post) {
                continue;
            }

            $item = $channel->addChild('item');
            $title = $post->getTitle();
            $title = ('~' === substr($title, 0, 1)) ? '* * *' : $title;

            self::appendXmlElement($item, 'title', $title);
            self::appendXmlElement($item, 'guid', $posts->getPostIndex($post), ['isPermaLink' => 'false']);
            self::appendXmlElement($item, 'link', Config::get()->url.'/'.$post->getSlug().'/');
            self::appendXmlElement($item, 'pubDate', $post->getFormattedDate(DATE_RSS));
            self::appendXmlElement($item, 'category', $post->getCategory());
            self::appendXmlElement($item, 'description', ContentFormatter::formatRssContent($post->getContent()));
        }
    }
}
