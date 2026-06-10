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

                // Get alternates for all languages
                $alternates = [];
                foreach ($this->kirby->languages() as $language) {
                    $alternates[] = [
                        'lang' => $language->code(),
                        'url' => $page->url($language->code())
                    ];
                }

                // Add URL for default language
                $sitemap[] = [
                    'url' => $page->url($defaultLanguage->code()),
                    'lastmod' =>  (date('c', $timestamp)),
                    'changefreq' => $this->getChangeFrequency($page),
                    'priority' => $this->getPriority($page),
                    'alternates' => $alternates
                ];
            } else {
                // Single language site
                $sitemap[] = [
                    'url' => $page->url(),
                    'lastmod' =>  (date('c', $timestamp)),
                    'changefreq' => $this->getChangeFrequency($page),
                    'priority' => $this->getPriority($page)
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
