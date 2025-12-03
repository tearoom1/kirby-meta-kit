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

        $title = $page->title()->value();

        if (!$seoData) {
            return $title;
        }

        // Get page title
        if ($type === 'og' && $seoData->ogTitle()->isNotEmpty()) {
            $title = $seoData->ogTitle()->value();
        } else if ($seoData->metaTitle()->isNotEmpty()) {
            $title = $seoData->metaTitle()->value();
        }

        // Get site SEO settings
        $siteSeo = self::getSeoData($site->metaKitSeo());
        if (!$siteSeo) {
            return $title;
        }


        // Check if site name should be appended based on type and settings
        $appendSiteName = $siteSeo->appendSiteName()->isNotEmpty() && $siteSeo->appendSiteName()->toBool();

        if ($appendSiteName) {

            // Get site meta title (with fallback to site title)
            if ($siteSeo->metaTitle()->isNotEmpty()) {
                $siteMetaTitle = $siteSeo->metaTitle()->value();
            } else {
                $siteMetaTitle = $site->title()->value();
            }

            // Get site settings
            $separator = $siteSeo->titleSeparator()->isNotEmpty()
                ? $siteSeo->titleSeparator()->value()
                : '|';

            $appendSiteNameTo = $siteSeo->appendSiteNameTo()->isNotEmpty()
                ? $siteSeo->appendSiteNameTo()->value()
                : null;

            if ($appendSiteNameTo && !empty($siteMetaTitle)) {
                $types = array_map('trim', explode(',', $appendSiteNameTo));
                $appendSiteName = in_array($type, $types);

                // Append site name if enabled and not already included
                if ($appendSiteName) {
                    $title = $title . ' ' . $separator . ' ' . $siteMetaTitle;
                }
            }
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
