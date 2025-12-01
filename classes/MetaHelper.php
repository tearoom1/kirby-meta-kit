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

    public static function buildTitle(Page $page, Site $site, $seoData, $type): string
    {
        // Get site SEO settings
        $siteSeo = self::getSeoData($site->metaKitSeo());

        // Get page title
        if ($type === 'og' && $seoData->ogTitle()->isNotEmpty()) {
            $title = $seoData->ogTitle()->value();
        } else if ($seoData->metaTitle()->isNotEmpty()) {
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

        // Get site settings
        $separator = $siteSeo && $siteSeo->titleSeparator()->isNotEmpty()
            ? $siteSeo->titleSeparator()->value()
            : '|';

        // Check if site name should be appended based on type and settings
        $appendSiteName = false;
        if ($siteSeo) {
            $appendSiteNameTo = $siteSeo->appendSiteNameTo()->isNotEmpty()
                ? $siteSeo->appendSiteNameTo()->value()
                : null;

            if ($appendSiteNameTo === null || $appendSiteNameTo === '') {
                // Fallback to old behavior if appendSiteNameTo is not set
                $appendSiteName = $siteSeo->appendSiteName()->isNotEmpty()
                    ? $siteSeo->appendSiteName()->toBool()
                    : false;
            } else {
                // appendSiteNameTo is a comma-separated string like "meta,og" or "meta" or "og"
                $types = array_map('trim', explode(',', $appendSiteNameTo));
                $appendSiteName = in_array($type, $types);
            }
        }

        // Append site name if enabled and not already included
        if ($appendSiteName && $siteMetaTitle && !str_contains($title, $siteMetaTitle)) {
            $title = $title . ' ' . $separator . ' ' . $siteMetaTitle;
        }

        return $title;
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

        return '';
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

        return '';
    }
}
