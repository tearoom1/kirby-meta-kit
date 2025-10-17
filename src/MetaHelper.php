<?php

namespace TearoomOne;

use Kirby\Cms\Page;
use Kirby\Cms\Site;

class MetaHelper
{
    public static function buildTitle(Page $page, Site $site, $seoData = null): string
    {
        // Get site settings
        $separator = $site->titleSeparator()->or('|')->value();
        $appendSiteName = $site->appendSiteName()->or('true')->toBool();

        // Get page title
        if ($seoData && method_exists($seoData, 'metaTitle') && $seoData->metaTitle()->isNotEmpty()) {
            $title = $seoData->metaTitle()->value();
        } else {
            $title = $page->title()->value();
        }

        // Get site meta title (with fallback to site title)
        if ($site->metaTitle()->isNotEmpty()) {
            $siteMetaTitle = $site->metaTitle()->value();
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
        return $site->titleSeparator()->or('|')->value();
    }

    public static function shouldAppendSiteName(Site $site): bool
    {
        return $site->appendSiteName()->or('true')->toBool();
    }

    public static function getSiteMetaTitle(Site $site): string
    {
        if ($site->metaTitle()->isNotEmpty()) {
            return $site->metaTitle()->value();
        }
        return $site->title()->value();
    }

    public static function buildDescription(Page $page, Site $site, $seoData = null, int $maxLength = 160): string
    {
        // Check page SEO data first
        if ($seoData && method_exists($seoData, 'metaDescription') && $seoData->metaDescription()->isNotEmpty()) {
            return $seoData->metaDescription()->excerpt($maxLength);
        }

        // Fall back to site default description
        if ($site->metaDescription()->isNotEmpty()) {
            return $site->metaDescription()->excerpt($maxLength);
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

        // Fall back to site OG description
        if ($site->ogDescription()->isNotEmpty()) {
            return $site->ogDescription()->excerpt($maxLength);
        }

        // Final fallback to page text
        return $page->text()->excerpt($maxLength);
    }
}
