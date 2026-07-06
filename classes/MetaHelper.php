<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Language;
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

    /**
     * Return the BCP 47 language code for the current request.
     * Multilang: taken from the active Kirby language.
     * Single-lang: derived from the site's locale option, falling back to "en".
     */
    public static function currentLanguageCode(Kirby $kirby): string
    {
        if ($kirby->multilang()) {
            return $kirby->language()->code();
        }

        $locale = $kirby->option('locale');

        // Kirby allows locale as string or [LC_* => string] array
        if (is_array($locale)) {
            $locale = $locale[LC_ALL] ?? reset($locale);
        }

        if ($locale) {
            // Strip encoding suffix (e.g. "de_DE.UTF-8" → "de_DE"), normalise to BCP 47
            $locale = preg_replace('/\..+$/', '', (string) $locale);
            $locale = str_replace('_', '-', $locale);
            // Return just the primary subtag (e.g. "de-DE" → "de-DE" is fine for inLanguage)
            return $locale;
        }

        return 'en';
    }

    /**
     * Convert a Kirby Language to an OG locale string (e.g. "de_DE", "en_US").
     * Uses the locale defined in the language file when available; falls back
     * to repeating the language code as the region (e.g. "de" → "de_DE").
     */
    public static function ogLocale(Language $language): string
    {
        // Kirby locale may be "de_AT.UTF-8", "de-AT", or just "de"
        $locale = $language->locale(LC_ALL);

        if ($locale) {
            $locale = str_replace('-', '_', $locale);
            // Strip encoding suffix (e.g. .UTF-8)
            $locale = preg_replace('/\..+$/', '', $locale);
            // Validate ll_CC format
            if (preg_match('/^([a-z]{2,3})_([A-Za-z]{2,4})$/', $locale, $m)) {
                return strtolower($m[1]) . '_' . strtoupper($m[2]);
            }
        }

        // Fallback: split on separator (e.g. "en-US" → en_US, "de" → de_DE)
        $code = str_replace('-', '_', $language->code());
        $parts = explode('_', $code);
        $lang = strtolower($parts[0]);
        $region = strtoupper($parts[1] ?? $parts[0]);

        return $lang . '_' . $region;
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
