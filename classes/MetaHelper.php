<?php

namespace TearoomOne;

use Kirby\Cms\Page;
use Kirby\Cms\Site;

class MetaHelper
{
    /**
     * Get data from a blocks field (used for site settings like openrouter, sitemap, robots)
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

        // For flat fields, read directly from page
        if ($type === 'og' && $page->ogTitle()->isNotEmpty()) {
            $title = $page->ogTitle()->value();
        } else if ($page->metaTitle()->isNotEmpty()) {
            $title = $page->metaTitle()->value();
        }

        // Get site SEO settings (flat fields)
        $appendSiteName = $site->appendSiteName()->isNotEmpty() && $site->appendSiteName()->toBool();

        if (!$appendSiteName) {
            return $title;
        }

        // Get site meta title (with fallback to site title)
        if ($site->metaTitle()->isNotEmpty()) {
            $siteMetaTitle = $site->metaTitle()->value();
        } else {
            $siteMetaTitle = $site->title()->value();
        }

        // Get site settings (flat fields)
        $separator = $site->titleSeparator()->isNotEmpty()
            ? $site->titleSeparator()->value()
            : '|';

        $appendSiteNameTo = $site->appendSiteNameTo()->isNotEmpty()
            ? $site->appendSiteNameTo()->value()
            : null;

        if ($appendSiteNameTo && !empty($siteMetaTitle)) {
            $types = array_map('trim', explode(',', $appendSiteNameTo));
            $shouldAppend = in_array($type, $types);

            // Append site name if enabled
            if ($shouldAppend) {
                $title = $title . ' ' . $separator . ' ' . $siteMetaTitle;
            }
        }

        return $title;
    }

    public static function buildDescription(Page $page, Site $site, $seoData = null, int $maxLength = 160): string
    {
        // Check page SEO field directly (flat field)
        if ($page->metaDescription()->isNotEmpty()) {
            return $page->metaDescription()->excerpt($maxLength);
        }

        // Fall back to site default description (flat field)
        if ($site->metaDescription()->isNotEmpty()) {
            return $site->metaDescription()->excerpt($maxLength);
        }

        return '';
    }

    public static function buildOgDescription(Page $page, Site $site, $seoData = null, ?string $metaDescription = null, int $maxLength = 160): string
    {
        // Check OG-specific description first (flat field)
        if ($page->ogDescription()->isNotEmpty()) {
            return $page->ogDescription()->excerpt($maxLength);
        }

        // Fall back to meta description (flat field)
        if ($page->metaDescription()->isNotEmpty()) {
            return $page->metaDescription()->excerpt($maxLength);
        }

        // Use provided meta description
        if ($metaDescription) {
            return $metaDescription;
        }

        // Fall back to site default description (flat field)
        if ($site->metaDescription()->isNotEmpty()) {
            return $site->metaDescription()->excerpt($maxLength);
        }

        return '';
    }
}
