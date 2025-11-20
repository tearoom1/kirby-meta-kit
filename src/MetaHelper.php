<?php

namespace TearoomOne;

use Kirby\Cms\Page;
use Kirby\Cms\Site;

class MetaHelper
{
    /**
     * Get SEO data from blocks or object field
     */
    public static function getSeoData($field)
    {
        if (!$field || $field->isEmpty()) {
            return null;
        }

        // Try blocks format first
        $blocks = $field->toBlocks();
        if ($blocks && $blocks->count() > 0) {
            return $blocks->first()->content();
        }

        // Fallback to object format (legacy)
        return $field->toObject();
    }

    public static function buildTitle(Page $page, Site $site, $seoData = null): string
    {
        // Get site SEO settings
        $siteSeo = self::getSeoData($site->metaKitSeo());

        // Get site settings
        $separator = $siteSeo && $siteSeo->titleSeparator()->isNotEmpty()
            ? $siteSeo->titleSeparator()->value()
            : '|';
        $appendSiteName = $siteSeo && $siteSeo->appendSiteName()->isNotEmpty()
            ? $siteSeo->appendSiteName()->toBool()
            : true;

        // Get page title
        if ($seoData && $seoData->metaTitle()->isNotEmpty()) {
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
        if ($appendSiteName && $siteMetaTitle && !str_contains($title, $siteMetaTitle)) {
            $title = $title . ' ' . $separator . ' ' . $siteMetaTitle;
        }

        return $title;
    }

    public static function getSeparator(Site $site): string
    {
        $siteSeo = self::getSeoData($site->metaKitSeo());
        return $siteSeo && $siteSeo->titleSeparator()->isNotEmpty()
            ? $siteSeo->titleSeparator()->value()
            : '|';
    }

    public static function shouldAppendSiteName(Site $site): bool
    {
        $siteSeo = self::getSeoData($site->metaKitSeo());
        return $siteSeo && $siteSeo->appendSiteName()->isNotEmpty()
            ? $siteSeo->appendSiteName()->toBool()
            : true;
    }

    public static function getSiteMetaTitle(Site $site): string
    {
        $siteSeo = self::getSeoData($site->metaKitSeo());
        if ($siteSeo && $siteSeo->metaTitle()->isNotEmpty()) {
            return $siteSeo->metaTitle()->value();
        }
        return $site->title()->value();
    }

    public static function buildDescription(Page $page, Site $site, $seoData = null, int $maxLength = 160): string
    {
        // Check page SEO data first
        if ($seoData && $seoData->metaDescription()->isNotEmpty()) {
            return $seoData->metaDescription()->excerpt($maxLength);
        }

        // Get site SEO settings
        $siteSeo = self::getSeoData($site->metaKitSeo());

        // Fall back to site default description
        if ($siteSeo && $siteSeo->metaDescription()->isNotEmpty()) {
            return $siteSeo->metaDescription()->excerpt($maxLength);
        }

        // Final fallback to page text excerpt
        return $page->text()->excerpt($maxLength);
    }

    public static function buildOgDescription(Page $page, Site $site, $seoData = null, ?string $metaDescription = null, int $maxLength = 160): string
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

        // Get site SEO settings
        $siteSeo = self::getSeoData($site->metaKitSeo());

        // Fall back to site OG description
        if ($siteSeo && $siteSeo->ogDescription()->isNotEmpty()) {
            return $siteSeo->ogDescription()->excerpt($maxLength);
        }

        // Final fallback to page text
        return $page->text()->excerpt($maxLength);
    }
}
