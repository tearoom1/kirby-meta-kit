<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Language;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Content\Field;
use Kirby\Toolkit\Str;

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

    /**
     * Meta description for the output: the stored text in full, as plain text.
     * The length ranges only guide new input in the Panel; pass $maxLength to
     * shorten explicitly (e.g. for a derived fallback).
     */
    public static function buildDescription(Page $page, Site $site, ?int $maxLength = null): string
    {
        // Check page SEO field directly (flat field)
        if ($page->metaDescription()->isNotEmpty()) {
            return self::outputText($page->metaDescription()->kirbytext()->value(), $maxLength);
        }

        // Fall back to site default description (flat field)
        if ($site->metaDescription()->isNotEmpty()) {
            return self::outputText($site->metaDescription()->kirbytext()->value(), $maxLength);
        }

        return '';
    }

    /**
     * Plain text of a stored value, shortened only when a limit is given
     */
    public static function outputText(string $text, ?int $maxLength = null): string
    {
        if ($maxLength === null) {
            return trim(Str::excerpt($text, 0));
        }

        return self::limitText($text, $maxLength);
    }

    /**
     * Plain-text excerpt that never exceeds $maxLength, ellipsis included
     */
    public static function limitText(string $text, int $maxLength): string
    {
        $plain = Str::excerpt($text, 0);
        if (mb_strlen($plain) <= $maxLength) {
            return $plain;
        }

        return Str::excerpt($plain, $maxLength - 2, rep: ' …');
    }

    /**
     * Upper end of the warning range for a field type. No longer applied to
     * stored values in the output; kept for callers that shorten derived text.
     */
    public static function outputLimit(string $fieldType, Page $page): int
    {
        $ranges = ConfigHelper::getValidationRanges($fieldType, $page->intendedTemplate()->name());
        return (int)($ranges['warning']['max'] ?? $ranges['optimal']['max']);
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

    public static function buildOgDescription(Page $page, Site $site, ?string $metaDescription = null, ?int $maxLength = null): string
    {
        // Check OG-specific description first (flat field)
        if ($page->ogDescription()->isNotEmpty()) {
            return self::outputText($page->ogDescription()->kirbytext()->value(), $maxLength);
        }

        // Fall back to meta description (flat field)
        if ($page->metaDescription()->isNotEmpty()) {
            return self::outputText($page->metaDescription()->kirbytext()->value(), $maxLength);
        }

        // Use provided meta description
        if ($metaDescription) {
            return $metaDescription;
        }

        // Fall back to site default description (flat field)
        if ($site->metaDescription()->isNotEmpty()) {
            return self::outputText($site->metaDescription()->kirbytext()->value(), $maxLength);
        }

        return '';
    }

    /**
     * Modification timestamp of a page in a language, or null when unknown.
     *
     * Kirby returns null when the language has no content file of its own
     * (the translation falls back to the default language). Then the default
     * language's content file counts. Without any content file the result is
     * null: callers must leave the date out instead of using the current time.
     *
     * @param string|null $languageCode Language to check, null for the current one
     */
    public static function modifiedTimestamp(Page $page, ?string $languageCode = null): ?int
    {
        $modified = $page->modified(null, null, $languageCode);

        if (is_int($modified) === false) {
            $default = $page->kirby()->defaultLanguage()?->code();
            if ($default !== null && $default !== $languageCode) {
                $modified = $page->modified(null, null, $default);
            }
        }

        return is_int($modified) && $modified > 0 ? $modified : null;
    }

    /**
     * Site title as a string, empty when the site has no title
     */
    public static function siteTitle(Site $site): string
    {
        return trim((string)$site->title()->value());
    }

    /**
     * Parents of a page that belong in the JSON-LD breadcrumb, top level
     * first. Pages a visitor can't open are left out, so the breadcrumb
     * never links to a 404:
     * - drafts
     * - pages without a template file (Kirby can't render them)
     * - pages whose template is listed in `schema.breadcrumb.excludeTemplates`
     *   (e.g. grouping pages that a route answers with a 404)
     * Unlisted and noindex parents stay: they still return 200, and a
     * breadcrumb item only has to be a reachable URL, not an indexed one.
     *
     * @return \Kirby\Cms\Page[]
     */
    public static function breadcrumbParents(Page $page): array
    {
        $excluded = array_map(
            'strtolower',
            (array)option('tearoom1.meta-kit.schema.breadcrumb.excludeTemplates', [])
        );

        return array_values(array_filter(
            $page->parents()->flip()->values(),
            fn (Page $parent) => $parent->isDraft() === false
                && $parent->template()->exists() === true
                && in_array(strtolower($parent->intendedTemplate()->name()), $excluded, true) === false
        ));
    }
}
