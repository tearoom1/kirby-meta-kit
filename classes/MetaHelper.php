<?php

namespace TearoomOne;

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Content\Field;

class MetaHelper
{
    /**
     * Extract the settings object from a blocks field, falling back to a plain object field.
     * Returns null if the field is empty.
     */
    public static function getSeoData(Field $field): ?object
    {
        if ($field->isEmpty()) {
            return null;
        }

        // Try blocks format first
        $blocks = $field->toBlocks();
        if ($blocks->count() > 0) {
            return $blocks->first()->content();
        }

        return $field->toObject();
    }

    public static function buildTitle(Page $page, Site $site, $type): string
    {
        $title = $page->title()->value();

        // For flat fields, read directly from page
        if ($type === 'og' && $page->ogTitle()->isNotEmpty()) {
            $title = $page->ogTitle()->value();
        } else if ($page->metaTitle()->isNotEmpty()) {
            $title = $page->metaTitle()->value();
        }

        $settings = ConfigHelper::getSiteSettings();

        if (!$settings['appendSiteName']) {
            return $title;
        }

        $appendToTypes = !empty($settings['appendSiteNameTo'])
            ? array_map('trim', explode(',', $settings['appendSiteNameTo']))
            : [];
        if (in_array($type, $appendToTypes) && !empty($settings['siteMetaTitle'])) {
            $title = $title . ' ' . $settings['titleSeparator'] . ' ' . $settings['siteMetaTitle'];
        }

        return $title;
    }

    public static function buildDescription(Page $page, Site $site, int $maxLength = 160): string
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

    public static function buildOgDescription(Page $page, Site $site, ?string $metaDescription = null, int $maxLength = 160): string
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
