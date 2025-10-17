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
        $this->options = $kirby->option('tearoom1.meta-kit', []);
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
                    'lastmod' => $page->modified('c'),
                    'changefreq' => $this->getChangeFrequency($page),
                    'priority' => $this->getPriority($page),
                    'alternates' => $alternates
                ];
            } else {
                // Single language site
                $sitemap[] = [
                    'url' => $page->url(),
                    'lastmod' => $page->modified('c'),
                    'changefreq' => $this->getChangeFrequency($page),
                    'priority' => $this->getPriority($page)
                ];
            }
        }

        return $sitemap;
    }

    protected function shouldInclude(Page $page): bool
    {
        // Skip unlisted and draft pages
        if ($page->isDraft() || $page->isUnlisted()) {
            return false;
        }

        // Check page's noIndex field
        $seoData = $page->seo()->toObject();
        if ($seoData && $seoData->noIndex()->toBool() === true) {
            return false;
        }

        // Check site blueprint's sitemapExclude field (pages selector)
        $siteExclude = $this->kirby->site()->sitemapExclude();
        if ($siteExclude->isNotEmpty()) {
            $excludedPages = $siteExclude->toPages();
            foreach ($excludedPages as $excludedPage) {
                if ($excludedPage->id() === $page->id()) {
                    return false;
                }
            }
        }

        // Check config exclude patterns
        $excludePatterns = $this->options['sitemap.exclude'] ?? [];
        $id = $page->id();

        foreach ($excludePatterns as $pattern) {
            if (preg_match("#{$pattern}#", $id)) {
                return false;
            }
        }

        return true;
    }

    protected function getChangeFrequency(Page $page): string
    {
        // Default change frequency based on page depth
        $depth = $page->depth();

        if ($depth <= 1) return 'weekly';
        if ($depth === 2) return 'monthly';
        return 'yearly';
    }

    protected function getPriority(Page $page): float
    {
        // Use site settings if available
        $site = $this->kirby->site();

        // Homepage gets priority from site settings or 1.0
        if ($page->isHomePage()) {
            return $site->sitemapPriorityHome()->toFloat() ?? 1.0;
        }

        // Other pages get default priority from site settings
        $defaultPriority = $site->sitemapPriorityDefault()->toFloat() ?? null;
        if ($defaultPriority !== null) {
            return $defaultPriority;
        }

        // Fallback: Priority based on page depth
        $depth = $page->depth();

        if ($depth <= 1) return 1.0;
        if ($depth === 2) return 0.8;
        if ($depth === 3) return 0.6;
        return 0.4;
    }
}
