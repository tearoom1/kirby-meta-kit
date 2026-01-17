<?php

namespace TearoomOne;

class LegacyMigration
{

    public static function convertAllLegacyFields(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $converted = 0;
        $skipped = 0;
        $errors = 0;

        // Convert Site first
        try {
            $siteResult = self::convertLegacyMetadata('site');
            if ($siteResult['status'] === 'success') {
                $converted++;
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
                $result = self::convertLegacyMetadata($page->id());
                if ($result['status'] === 'success') {
                    $converted++;
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
            'message' => "Migrated {$converted} pages, skipped {$skipped}, errors {$errors}",
            'converted' => $converted,
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

    /**
     * Convert legacy metadata for all languages (default first)
     */
    public static function convertAllLanguages(): array
    {
        $kirby     = kirby();
        $languages = $kirby->languages();

        // Single-language sites: reuse existing converter
        if ($languages->count() === 0) {
            return self::convertAllLegacyFields();
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

        $converted = 0; $skipped = 0; $errors = 0;
        $current = $kirby->language()?->code();

        foreach ($ordered as $lang) {
            try {
                $kirby->setCurrentLanguage($lang->code());
                $res = self::convertAllLegacyFields();
                $converted += $res['converted'] ?? 0;
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
            'message'   => "Migrated {$converted} items, skipped {$skipped}, errors {$errors} across all languages",
            'converted' => $converted,
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
        $siteLegacy = [];
        $siteCurrent = [];

        // Check for old metaKitSeo blocks field on site
        $siteBlocks = self::getBlocksData($site, 'metaKitSeo');
        if ($siteBlocks) {
            foreach (['appendSiteName', 'appendSiteNameTo', 'titleSeparator', 'metaTitle', 'metaDescription', 'ogImage'] as $field) {
                if (isset($siteBlocks[$field]) && !empty($siteBlocks[$field])) {
                    $siteLegacy[$field] = is_array($siteBlocks[$field]) ? json_encode($siteBlocks[$field]) : $siteBlocks[$field];
                    $siteCurrent[$field] = $site->$field()->isNotEmpty() ? $site->$field()->value() : null;
                }
            }
        }

        // Check for legacy field names
        if ($site->metatitle()->isNotEmpty()) {
            $siteLegacy['metaTitle (legacy)'] = $site->metatitle()->value();
            $siteCurrent['metaTitle'] = $site->metaTitle()->isNotEmpty()
                ? $site->metaTitle()->value()
                : null;
        }
        if ($site->metadescription()->isNotEmpty()) {
            $siteLegacy['metaDescription (legacy)'] = $site->metadescription()->value();
            $siteCurrent['metaDescription'] = $site->metaDescription()->isNotEmpty()
                ? $site->metaDescription()->value()
                : null;
        }
        if ($site->ogimage()->isNotEmpty()) {
            $files = $site->ogimage()->toFiles();
            if ($files->count() > 0) {
                $siteLegacy['ogImage (legacy)'] = $files->first()->filename();
                $siteCurrent['ogImage'] = $site->ogImage()->isNotEmpty()
                    ? $site->ogImage()->toFiles()->first()?->filename()
                    : null;
            }
        }
        if (!empty($siteLegacy)) {
            $found[] = [
                'id' => 'site',
                'title' => $site->title()->value(),
                'fields' => $siteLegacy,
                'current' => $siteCurrent
            ];
        }

        // Check Pages
        foreach ($pages as $page) {
            $legacy = [];
            $current = [];

            // Check for old metaKitSeo blocks field on page
            $pageBlocks = self::getBlocksData($page, 'metaKitSeo');
            if ($pageBlocks) {
                foreach (['metaTitle', 'metaDescription', 'ogTitle', 'ogDescription', 'ogImage', 'robots', 'canonicalUrl', 'metaAuthor'] as $field) {
                    if (isset($pageBlocks[$field]) && !empty($pageBlocks[$field])) {
                        $legacy[$field] = is_array($pageBlocks[$field]) ? json_encode($pageBlocks[$field]) : $pageBlocks[$field];
                        $current[$field] = $page->$field()->isNotEmpty() ? $page->$field()->value() : null;
                    }
                }
            }

            // Check for legacy field names
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle (legacy)'] = $page->metatitle()->value();
                $current['metaTitle'] = $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null;
            } elseif ($page->meta_title()->isNotEmpty()) {
                $legacy['metaTitle (legacy)'] = $page->meta_title()->value();
                $current['metaTitle'] = $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null;
            }

            if ($page->metadescription()->isNotEmpty()) {
                $legacy['metaDescription (legacy)'] = $page->metadescription()->value();
                $current['metaDescription'] = $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null;
            } elseif ($page->meta_description()->isNotEmpty()) {
                $legacy['metaDescription (legacy)'] = $page->meta_description()->value();
                $current['metaDescription'] = $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null;
            }

            if ($page->ogimage()->isNotEmpty()) {
                $files = $page->ogimage()->toFiles();
                if ($files->count() > 0) {
                    $legacy['ogImage (legacy)'] = $files->first()->filename();
                    $current['ogImage'] = $page->ogImage()->isNotEmpty()
                        ? $page->ogImage()->toFiles()->first()?->filename()
                        : null;
                }
            }

            if (!empty($legacy)) {
                $found[] = [
                    'id' => $page->id(),
                    'title' => $page->title()->value(),
                    'fields' => $legacy,
                    'current' => $current
                ];
            }
        }

        return [
            'status' => 'success',
            'found' => count($found),
            'pages' => $found
        ];
    }

    /**
     * Extract data from a blocks field (old metaKitSeo format)
     * Returns normalized array with camelCase keys
     */
    protected static function getBlocksData($model, string $fieldName): ?array
    {
        $field = $model->content()->get($fieldName);
        if (!$field || $field->isEmpty()) {
            return null;
        }

        try {
            $blocks = $field->toBlocks();
            if ($blocks && $blocks->count() > 0) {
                $block = $blocks->first();
                $data = $block->content()->toArray();

                // Normalize keys to camelCase (Kirby stores as lowercase)
                $normalized = [];
                $keyMap = [
                    'metatitle' => 'metaTitle',
                    'metadescription' => 'metaDescription',
                    'metaauthor' => 'metaAuthor',
                    'metakeywords' => 'metaKeywords',
                    'ogtitle' => 'ogTitle',
                    'ogdescription' => 'ogDescription',
                    'ogimage' => 'ogImage',
                    'canonicalurl' => 'canonicalUrl',
                    'appendsitename' => 'appendSiteName',
                    'appendsitenameto' => 'appendSiteNameTo',
                    'titleseparator' => 'titleSeparator',
                    'robots' => 'robots',
                ];

                foreach ($data as $key => $value) {
                    $normalizedKey = $keyMap[strtolower($key)] ?? $key;
                    $normalized[$normalizedKey] = $value;
                }

                return $normalized;
            }
        } catch (\Exception $e) {
            // Field might not be in blocks format
        }

        return null;
    }

    public static function convertLegacyMetadata(string $pageId): array
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
            $converted = [];
            $updateData = [];
            $languageCode = $kirby->language()?->code();

            // First, migrate from old metaKitSeo blocks to flat fields
            $blocksData = self::getBlocksData($page, 'metaKitSeo');
            if ($blocksData) {
                if ($isSite) {
                    // Site-specific fields from blocks
                    $siteFields = ['appendSiteName', 'appendSiteNameTo', 'titleSeparator', 'metaTitle', 'metaDescription', 'ogImage'];
                    foreach ($siteFields as $field) {
                        if (isset($blocksData[$field]) && !empty($blocksData[$field]) && $page->$field()->isEmpty()) {
                            $value = $blocksData[$field];
                            // Handle special cases
                            if ($field === 'appendSiteName') {
                                $updateData[$field] = (bool)$value;
                            } elseif ($field === 'ogImage' && is_array($value)) {
                                $updateData[$field] = $value;
                            } else {
                                $updateData[$field] = $value;
                            }
                            $converted[] = "{$field} (from metaKitSeo blocks)";
                        }
                    }
                } else {
                    // Page-specific fields from blocks
                    $pageFields = ['metaTitle', 'metaDescription', 'ogTitle', 'ogDescription', 'ogImage', 'robots', 'canonicalUrl', 'metaAuthor'];
                    foreach ($pageFields as $field) {
                        if (isset($blocksData[$field]) && !empty($blocksData[$field]) && $page->$field()->isEmpty()) {
                            $value = $blocksData[$field];
                            // Handle special cases
                            if ($field === 'ogImage' && is_array($value)) {
                                $updateData[$field] = $value;
                            } else {
                                $updateData[$field] = $value;
                            }
                            $converted[] = "{$field} (from metaKitSeo blocks)";
                        }
                    }
                }
            }

            if ($isSite) {
                // Site: Also migrate from legacy field names
                if ($page->metatitle()->isNotEmpty() && $page->metaTitle()->isEmpty() && !isset($updateData['metaTitle'])) {
                    $updateData['metaTitle'] = $page->metatitle()->value();
                    $converted[] = 'metaTitle (from metatitle)';
                } elseif ($page->meta_title()->isNotEmpty() && $page->metaTitle()->isEmpty() && !isset($updateData['metaTitle'])) {
                    $updateData['metaTitle'] = $page->meta_title()->value();
                    $converted[] = 'metaTitle (from meta_title)';
                }

                if ($page->metadescription()->isNotEmpty() && $page->metaDescription()->isEmpty() && !isset($updateData['metaDescription'])) {
                    $updateData['metaDescription'] = $page->metadescription()->value();
                    $converted[] = 'metaDescription (from metadescription)';
                } elseif ($page->meta_description()->isNotEmpty() && $page->metaDescription()->isEmpty() && !isset($updateData['metaDescription'])) {
                    $updateData['metaDescription'] = $page->meta_description()->value();
                    $converted[] = 'metaDescription (from meta_description)';
                }

                if ($page->ogimage()->isNotEmpty() && $page->ogImage()->isEmpty() && !isset($updateData['ogImage'])) {
                    $files = $page->ogimage()->toFiles();
                    if ($files && $files->count() > 0) {
                        $updateData['ogImage'] = self::filesToUuids($files);
                        $converted[] = 'ogImage (from ogimage)';
                    }
                }
            } else {
                // Pages: Also migrate from legacy field names
                if ($page->metatitle()->isNotEmpty() && $page->metaTitle()->isEmpty() && !isset($updateData['metaTitle'])) {
                    $updateData['metaTitle'] = $page->metatitle()->value();
                    $converted[] = 'metaTitle (from metatitle)';
                } elseif ($page->meta_title()->isNotEmpty() && $page->metaTitle()->isEmpty() && !isset($updateData['metaTitle'])) {
                    $updateData['metaTitle'] = $page->meta_title()->value();
                    $converted[] = 'metaTitle (from meta_title)';
                }

                if ($page->metadescription()->isNotEmpty() && $page->metaDescription()->isEmpty() && !isset($updateData['metaDescription'])) {
                    $updateData['metaDescription'] = $page->metadescription()->value();
                    $converted[] = 'metaDescription (from metadescription)';
                } elseif ($page->meta_description()->isNotEmpty() && $page->metaDescription()->isEmpty() && !isset($updateData['metaDescription'])) {
                    $updateData['metaDescription'] = $page->meta_description()->value();
                    $converted[] = 'metaDescription (from meta_description)';
                }

                if ($page->ogimage()->isNotEmpty() && $page->ogImage()->isEmpty() && !isset($updateData['ogImage'])) {
                    $files = $page->ogimage()->toFiles();
                    if ($files && $files->count() > 0) {
                        $updateData['ogImage'] = self::filesToUuids($files);
                        $converted[] = 'ogImage (from ogimage)';
                    }
                } elseif ($page->og_image()->isNotEmpty() && $page->ogImage()->isEmpty() && !isset($updateData['ogImage'])) {
                    $files = $page->og_image()->toFiles();
                    if ($files && $files->count() > 0) {
                        $updateData['ogImage'] = self::filesToUuids($files);
                        $converted[] = 'ogImage (from og_image)';
                    }
                }
            }

            // Check if translation file exists for this language (skip for site)
            $content = $page->content($languageCode);
            if ($isSite === false && !$content->exists()) {
                return [
                    'status' => 'info',
                    'message' => 'Translation file does not exist for language: ' . $languageCode
                ];
            }

            // List of all legacy SEO fields to remove
            $fieldsToDelete = [
                'metatemplate', 'usetitletemplate', 'ogtemplate',
                'useogtemplate', 'ogdescription', 'ogimage', 'cropogimage',
                'robotsindex', 'robotsfollow', 'robotsarchive', 'robotsimageindex',
                'robotssnippet', 'metainherit', 'twittercardtype', 'twitterauthor',
                'seo_title', 'seo_description',
                'metatitle', 'meta_title', 'metadescription', 'meta_description',
                'meta_canonical_url', 'meta_author', 'meta_image', 'meta_phone_number',
                'og_title', 'og_description', 'og_image', 'og_site_name', 'og_url',
                'og_audio', 'og_video', 'og_determiner', 'og_type',
                'og_type_article_published_time', 'og_type_article_modified_time',
                'og_type_article_expiration_time', 'og_type_article_author',
                'og_type_article_section', 'og_type_article_tag',
                'twitter_title', 'twitter_description', 'twitter_image',
                'twitter_card_type', 'twitter_site', 'twitter_creator',
                'robots_noindex', 'robots_nofollow', 'robots_noarchive',
                'robots_noimageindex', 'robots_nosnippet',
                'metakitseo' // Also remove old blocks field from pages
            ];


            foreach ($fieldsToDelete as $fieldToDelete) {
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

                if (!empty($converted)) {
                    return [
                        'status' => 'success',
                        'message' => 'Converted: ' . implode(', ', $converted) . ' and removed legacy fields',
                        'converted' => $converted
                    ];
                } else {
                    return [
                        'status' => 'success',
                        'message' => 'Removed legacy fields',
                        'converted' => []
                    ];
                }
            }

            return [
                'status' => 'info',
                'message' => 'No legacy fields found or already converted'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert files collection to array of UUID strings
     */
    protected static function filesToUuids($files): array
    {
        $uuids = [];
        foreach ($files as $file) {
            if ($uuid = $file->uuid()) {
                $uuids[] = $uuid->toString();
            }
        }
        return $uuids;
    }
}
