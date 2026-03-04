<?php

namespace TearoomOne;

class LegacyCleanup
{
    private static function legacyFieldNames(): array
    {
        return [
            'metatemplate',
            'usetitletemplate',
            'ogtemplate',
            'useogtemplate',
            'ogdescription',
            'ogimage',
            'cropogimage',
            'robotsindex',
            'robotsfollow',
            'robotsarchive',
            'robotsimageindex',
            'robotssnippet',
            'metainherit',
            'twittercardtype',
            'twitterauthor',
            'seo_title',
            'seo_description',
            'metatitle',
            'meta_title',
            'metadescription',
            'meta_description',
            'meta_canonical_url',
            'meta_author',
            'meta_image',
            'meta_phone_number',
            'og_title',
            'og_description',
            'og_image',
            'og_site_name',
            'og_url',
            'og_audio',
            'og_video',
            'og_determiner',
            'og_type',
            'og_type_article_published_time',
            'og_type_article_modified_time',
            'og_type_article_expiration_time',
            'og_type_article_author',
            'og_type_article_section',
            'og_type_article_tag',
            'twitter_title',
            'twitter_description',
            'twitter_image',
            'twitter_card_type',
            'twitter_site',
            'twitter_creator',
            'robots_noindex',
            'robots_nofollow',
            'robots_noarchive',
            'robots_noimageindex',
            'robots_nosnippet',
            // Old blocks field from previous plugin versions
            'metakitseo',
        ];
    }

    private static function detectLegacyFieldsForModel($model, $content): array
    {
        $legacyFields = [];

        foreach (self::legacyFieldNames() as $fieldName) {
            if ($content->has($fieldName) === false) {
                continue;
            }

            // Keep value for debug/visibility. Empty values still count as legacy.
            $value = $model->{$fieldName}()->value();
            $legacyFields[$fieldName] = $value === '' ? '(empty)' : $value;
        }

        return $legacyFields;
    }

    public static function cleanupAllLegacyFields(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $cleaned = 0;
        $skipped = 0;
        $errors = 0;

        // Cleanup Site first
        try {
            $siteResult = self::cleanupLegacyFields('site');
            if ($siteResult['status'] === 'success') {
                $cleaned++;
            } elseif ($siteResult['status'] === 'info') {
                $skipped++;
            } else {
                $errors++;
            }
        } catch (\Exception $e) {
            $errors++;
        }

        foreach ($pages as $page) {
            try {
                $result = self::cleanupLegacyFields($page->id());
                if ($result['status'] === 'success') {
                    $cleaned++;
                } elseif ($result['status'] === 'info') {
                    $skipped++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return [
            'status' => 'success',
            'message' => "Cleaned {$cleaned} items, skipped {$skipped}, errors {$errors}",
            'cleaned' => $cleaned,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Detect legacy metadata across all languages and return a summary
     * Order: default language first, then remaining languages
     */
    public static function detectLegacySummaryAllLanguages(): array
    {
        $kirby     = kirby();
        $languages = $kirby->languages();

        if ($languages->count() === 0) {
            $result = self::detectLegacyMetadata();
            return [
                'status' => 'success',
                'total'  => $result['found'] ?? 0,
                'byLanguage' => []
            ];
        }

        // Build default-first order
        $ordered = [];
        if ($default = $languages->default()) {
            $ordered[] = $default;
            $languages = $languages->not($default);
        }
        foreach ($languages as $lang) {
            $ordered[] = $lang;
        }

        $summary = [];
        $total   = 0;

        $current = $kirby->language()?->code();
        foreach ($ordered as $lang) {
            $kirby->setCurrentLanguage($lang->code());
            $res = self::detectLegacyMetadata();
            $count = $res['found'] ?? 0;
            $summary[] = [
                'code'  => $lang->code(),
                'name'  => $lang->name(),
                'count' => $count,
            ];
            $total += $count;
        }
        // Restore previous language
        if (!empty($current)) {
            $kirby->setCurrentLanguage($current);
        }

        return [
            'status' => 'success',
            'total'  => $total,
            'byLanguage' => $summary
        ];
    }

    public static function cleanupAllLanguages(): array
    {
        $kirby     = kirby();
        $languages = $kirby->languages();

        // Single-language sites: reuse existing cleanup
        if ($languages->count() === 0) {
            return self::cleanupAllLegacyFields();
        }

        // Build default-first order
        $ordered = [];
        if ($default = $languages->default()) {
            $ordered[] = $default;
            $languages = $languages->not($default);
        }
        foreach ($languages as $lang) {
            $ordered[] = $lang;
        }

        $cleaned = 0; $skipped = 0; $errors = 0;
        $current = $kirby->language()?->code();

        foreach ($ordered as $lang) {
            try {
                $kirby->setCurrentLanguage($lang->code());
                $res = self::cleanupAllLegacyFields();
                $cleaned   += $res['cleaned'] ?? 0;
                $skipped   += $res['skipped'] ?? 0;
                $errors    += $res['errors'] ?? 0;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        // Restore previous language
        if (!empty($current)) {
            $kirby->setCurrentLanguage($current);
        }

        return [
            'status'    => 'success',
            'message'   => "Cleaned {$cleaned} items, skipped {$skipped}, errors {$errors} across all languages",
            'cleaned'   => $cleaned,
            'skipped'   => $skipped,
            'errors'    => $errors,
        ];
    }

    public static function detectLegacyMetadata(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $found = [];

        // Check Site
        $site = $kirby->site();
        $siteLegacy = self::detectLegacyFieldsForModel(
            $site,
            $site->content($kirby->language()?->code())
        );
        if (!empty($siteLegacy)) {
            $found[] = [
                'id' => 'site',
                'title' => $site->title()->value(),
                'fields' => $siteLegacy,
                'current' => []
            ];
        }

        // Check Pages
        foreach ($pages as $page) {
            $content = $page->content($kirby->language()?->code());
            $legacy = self::detectLegacyFieldsForModel($page, $content);
            if (!empty($legacy)) {
                $found[] = [
                    'id' => $page->id(),
                    'title' => $page->title()->value(),
                    'fields' => $legacy,
                    'current' => []
                ];
            }
        }

        return [
            'status' => 'success',
            'found' => count($found),
            'pages' => $found
        ];
    }

    public static function cleanupLegacyFields(string $pageId): array
    {
        $kirby = kirby();
        $isSite = ($pageId === 'site');
        $page = $isSite ? $kirby->site() : $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        try {
            $updateData = [];
            $languageCode = $kirby->language()?->code();

            // Check if translation file exists for this language (skip for site)
            $content = $page->content($languageCode);
            if ($isSite === false && !$content->exists()) {
                return [
                    'status' => 'info',
                    'message' => 'Translation file does not exist for language: ' . $languageCode
                ];
            }

            foreach (self::legacyFieldNames() as $fieldToDelete) {
                // check if field exists in content (for site, use current language content file)
                if ($isSite === true) {
                    $siteContent = $languageCode ? $page->content($languageCode) : $page->content();
                    if ($siteContent->has($fieldToDelete)) {
                        $updateData[$fieldToDelete] = null;
                    }
                } else {
                    if ($content->has($fieldToDelete)) {
                        $updateData[$fieldToDelete] = null;
                    }
                }
            }

            // Only update if we have something to do
            if (count($updateData) > 0) {
                $page->update($updateData, $languageCode);

                return [
                    'status' => 'success',
                    'message' => 'Removed legacy fields'
                ];
            }

            return [
                'status' => 'info',
                'message' => 'No legacy fields found'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

}
