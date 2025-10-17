<?php

namespace TearoomOne;

use Kirby\Cms\Page;
use Kirby\Cms\Site;

class MetaHelper
{
    public static function buildTitle(Page $page, Site $site, $seoData = null): string
    {
        // Get site SEO settings object
        $siteSeo = $site->seo()->toObject();
        
        // Get site settings
        $separator = $siteSeo && $siteSeo->titleSeparator()->isNotEmpty() 
            ? $siteSeo->titleSeparator()->value() 
            : '|';
        $appendSiteName = $siteSeo && $siteSeo->appendSiteName()->isNotEmpty() 
            ? $siteSeo->appendSiteName()->toBool() 
            : true;

        // Get page title
        if ($seoData && method_exists($seoData, 'metaTitle') && $seoData->metaTitle()->isNotEmpty()) {
            $title = $seoData->metaTitle()->value();
        } else {
            $title = $page->title()->value();
        }

        // Get site meta title (with fallback to site title)
        if ($siteSeo && $siteSeo->metaTitle()->isNotEmpty()) {
            $siteMetaTitle = $siteSeo->metaTitle()->value();
        } else {
            $siteMetaTitle = $site->title()->value();
        }

        // Append site name if enabled and not already included
        if ($appendSiteName && !str_contains($title, $siteMetaTitle)) {
            $title = $title . ' ' . $separator . ' ' . $siteMetaTitle;
        }

        return $title;
    }

    public static function getSeparator(Site $site): string
    {
        $siteSeo = $site->seo()->toObject();
        return $siteSeo && $siteSeo->titleSeparator()->isNotEmpty() 
            ? $siteSeo->titleSeparator()->value() 
            : '|';
    }

    public static function shouldAppendSiteName(Site $site): bool
    {
        $siteSeo = $site->seo()->toObject();
        return $siteSeo && $siteSeo->appendSiteName()->isNotEmpty() 
            ? $siteSeo->appendSiteName()->toBool() 
            : true;
    }

    public static function getSiteMetaTitle(Site $site): string
    {
        $siteSeo = $site->seo()->toObject();
        if ($siteSeo && $siteSeo->metaTitle()->isNotEmpty()) {
            return $siteSeo->metaTitle()->value();
        }
        return $site->title()->value();
    }

    public static function buildDescription(Page $page, Site $site, $seoData = null, int $maxLength = 160): string
    {
        // Check page SEO data first
        if ($seoData && method_exists($seoData, 'metaDescription') && $seoData->metaDescription()->isNotEmpty()) {
            return $seoData->metaDescription()->excerpt($maxLength);
        }

        // Get site SEO settings object
        $siteSeo = $site->seo()->toObject();
        
        // Fall back to site default description
        if ($siteSeo && $siteSeo->metaDescription()->isNotEmpty()) {
            return $siteSeo->metaDescription()->excerpt($maxLength);
        }

        // Final fallback to page text excerpt
        return $page->text()->excerpt($maxLength);
    }

    public static function buildOgDescription(Page $page, Site $site, $seoData = null, string $metaDescription = null, int $maxLength = 160): string
    {
        // Check OG-specific description first
        if ($seoData && method_exists($seoData, 'ogDescription') && $seoData->ogDescription()->isNotEmpty()) {
            return $seoData->ogDescription()->excerpt($maxLength);
        }

        // Fall back to meta description (passed or generated)
        if ($seoData && method_exists($seoData, 'metaDescription') && $seoData->metaDescription()->isNotEmpty()) {
            return $seoData->metaDescription()->excerpt($maxLength);
        }

        // Use provided meta description
        if ($metaDescription) {
            return $metaDescription;
        }

        // Get site SEO settings object
        $siteSeo = $site->seo()->toObject();
        
        // Fall back to site OG description
        if ($siteSeo && $siteSeo->ogDescription()->isNotEmpty()) {
            return $siteSeo->ogDescription()->excerpt($maxLength);
        }

        // Final fallback to page text
        return $page->text()->excerpt($maxLength);
    }
}
