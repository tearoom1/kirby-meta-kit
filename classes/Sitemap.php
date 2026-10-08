<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;

class Sitemap
{
    protected $kirby;
    protected $options;

    public function __construct(Kirby $kirby)
    {
        $this->kirby = $kirby;
        $this->options = ConfigHelper::getSitemapSettings();
    }

    /**
     * The sitemap XML, from the plugin cache when possible. The cache is
     * flushed whenever pages, files or the site change (see hooks.php);
     * the duration only covers changes made outside of Kirby.
     */
    public static function render(Kirby $kirby): string
    {
        $sitemap = new static($kirby);

        if (($sitemap->options['sitemap.cache'] ?? true) !== true) {
            return $sitemap->toXml();
        }

        $cache = $kirby->cache('tearoom1.meta-kit.sitemap');
        $key = 'sitemap';

        if (($xml = $cache->get($key)) !== null) {
            return $xml;
        }

        $xml = $sitemap->toXml();
        $cache->set($key, $xml, (int)($sitemap->options['sitemap.cacheDuration'] ?? 60));

        return $xml;
    }

    public static function flushCache(): void
    {
        kirby()->cache('tearoom1.meta-kit.sitemap')->flush();
    }

    public function toXml(): string
    {
        $multilang = $this->kirby->multilang();
        $withImages = ($this->options['sitemap.images'] ?? true) === true;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ($multilang ? ' xmlns:xhtml="http://www.w3.org/1999/xhtml"' : '')
            . ($withImages ? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : '')
            . '>';

        foreach ($this->generate() as $item) {
            $xml .= "\n    <url>";
            $xml .= "\n        <loc>" . htmlspecialchars($item['url']) . "</loc>";
            $xml .= "\n        <lastmod>" . $item['lastmod'] . "</lastmod>";
            $xml .= "\n        <changefreq>" . $item['changefreq'] . "</changefreq>";
            $xml .= "\n        <priority>" . $item['priority'] . "</priority>";

            // Add alternate language links
            foreach ($item['alternates'] ?? [] as $alternate) {
                $xml .= "\n        <xhtml:link rel=\"alternate\" hreflang=\"" .
                    htmlspecialchars($alternate['lang']) . "\" href=\"" .
                    htmlspecialchars($alternate['url']) . "\" />";
            }

            foreach ($item['images'] ?? [] as $image) {
                $xml .= "\n        <image:image><image:loc>" . htmlspecialchars($image) . "</image:loc></image:image>";
            }

            $xml .= "\n    </url>";
        }

        return $xml . "\n</urlset>";
    }

    /**
     * Image URLs of a page for the image sitemap (Google reads up to 1000)
     */
    protected function images(Page $page): array
    {
        if (($this->options['sitemap.images'] ?? true) !== true) {
            return [];
        }

        return array_values($page->images()->limit(1000)->map(fn ($image) => $image->url())->data());
    }

    public function generate(): array
    {
        $sitemap = [];
        $pages = $this->kirby->site()->index();
        $isMultilang = $this->kirby->multilang();

        foreach ($pages as $page) {
            if (!$this->shouldInclude($page)) {
                continue;
            }

            // For multilanguage sites, add entry for each language
            $timestamp = $page->modified() ?? time();
            if ($isMultilang) {
                $defaultLanguage = $this->kirby->defaultLanguage();

                // Build alternates list: one per language + x-default pointing to default language
                $alternates = [];
                foreach ($this->kirby->languages() as $language) {
                    $alternates[] = [
                        'lang' => $language->code(),
                        'url' => $page->url($language->code())
                    ];
                }
                $alternates[] = [
                    'lang' => 'x-default',
                    'url' => $page->url($defaultLanguage->code())
                ];

                // Add one <url> entry per language so all versions are first-class sitemap entries
                foreach ($this->kirby->languages() as $language) {
                    $sitemap[] = [
                        'url' => $page->url($language->code()),
                        'lastmod' => (date('c', $timestamp)),
                        'changefreq' => $this->getChangeFrequency($page),
                        'priority' => $this->getPriority($page),
                        'alternates' => $alternates,
                        'images' => $this->images($page)
                    ];
                }
            } else {
                // Single language site
                $sitemap[] = [
                    'url' => $page->url(),
                    'lastmod' =>  (date('c', $timestamp)),
                    'changefreq' => $this->getChangeFrequency($page),
                    'priority' => $this->getPriority($page),
                    'images' => $this->images($page)
                ];
            }
        }

        return $sitemap;
    }

    protected function shouldInclude(Page $page): bool
    {
        // Always skip draft pages
        if ($page->isDraft()) {
            return false;
        }
        
        // Skip unlisted pages unless configured to include them
        $includeUnlisted = $this->options['sitemap.includeUnlisted'] ?? false;
        if ($page->isUnlisted() && !$includeUnlisted) {
            return false;
        }

        // Check page's robots directive (flat field)
        if ($page->robots()->isNotEmpty()) {
            $robots = $page->robots()->value();
            if (str_contains($robots, 'noindex')) {
                return false;
            }
        }

        // Check site blueprint's sitemapExclude field (pages selector)
        if (isset($this->options['sitemap.exclude.pages'])) {
            foreach ($this->options['sitemap.exclude.pages'] as $excludedPage) {
                if ($excludedPage->id() === $page->id()) {
                    return false;
                }
            }
        }

        // Check config exclude patterns
        $excludePatterns = $this->options['sitemap.exclude'] ?? [];
        $id = $page->id();

        foreach ($excludePatterns as $pattern) {
            // Use ~ delimiter so a `#` in patterns doesn't break the regex,
            // and silence warnings on malformed user-supplied patterns.
            if (@preg_match('~' . $pattern . '~', $id) === 1) {
                return false;
            }
        }

        return true;
    }

    protected function getChangeFrequency(Page $page): string
    {
        // Check page-level override first (most specific)
        if ($page->sitemapChangefreq()->isNotEmpty()) {
            return $page->sitemapChangefreq()->value();
        }
        
        $template = $page->intendedTemplate()->name();
        $slug = $page->slug();
        
        // Check slug-based rules
        $slugRules = $this->options['sitemap.changefreq.slugs'] ?? [];
        if (isset($slugRules[$slug])) {
            return $slugRules[$slug];
        }
        
        // Check template-based rules
        $templateRules = $this->options['sitemap.changefreq.templates'] ?? [];
        if (isset($templateRules[$template])) {
            return $templateRules[$template];
        }
        
        // Fall back to default
        return $this->options['sitemap.changefreq.default'] ?? 'monthly';
    }

    protected function getPriority(Page $page): float
    {
        // Check page-level override first (most specific)
        if ($page->sitemapPriority()->isNotEmpty()) {
            return $page->sitemapPriority()->toFloat();
        }
        
        $template = $page->intendedTemplate()->name();
        $slug = $page->slug();
        
        // Check slug-based rules
        $slugRules = $this->options['sitemap.priority.slugs'] ?? [];
        if (isset($slugRules[$slug])) {
            return $slugRules[$slug];
        }
        
        // Check template-based rules
        $templateRules = $this->options['sitemap.priority.templates'] ?? [];
        if (isset($templateRules[$template])) {
            return $templateRules[$template];
        }
        
        // Homepage gets priority from settings
        if ($page->isHomePage()) {
            return $this->options['sitemap.priorityHome'] ?? 1.0;
        }

        // Other pages get default priority from settings
        if (isset($this->options['sitemap.priorityDefault'])) {
            return $this->options['sitemap.priorityDefault'];
        }

        // Fallback: Priority based on page depth
        $depth = $page->depth();

        if ($depth <= 1) return 1.0;
        if ($depth === 2) return 0.8;
        if ($depth === 3) return 0.6;
        return 0.4;
    }
}
