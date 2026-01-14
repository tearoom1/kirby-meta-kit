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

        // Check Site (site uses blocks)
        $site = $kirby->site();
        $siteLegacy = [];
        $siteCurrent = [];
        $siteSeo = MetaKitController::getSiteSeoData($site);
        if ($site->metatitle()->isNotEmpty()) {
            $siteLegacy['metaTitle'] = $site->metatitle()->value();
            $siteCurrent['metaTitle'] = $siteSeo && $siteSeo->metaTitle()->isNotEmpty()
                ? $siteSeo->metaTitle()->value()
                : null;
        }
        if ($site->metadescription()->isNotEmpty()) {
            $siteLegacy['metaDescription'] = $site->metadescription()->value();
            $siteCurrent['metaDescription'] = $siteSeo && $siteSeo->metaDescription()->isNotEmpty()
                ? $siteSeo->metaDescription()->value()
                : null;
        }
        if ($site->ogimage()->isNotEmpty()) {
            $files = $site->ogimage()->toFiles();
            if ($files->count() > 0) {
                $siteLegacy['ogImage'] = $files->first()->filename();
                $siteCurrent['ogImage'] = $siteSeo && $siteSeo->ogImage()->isNotEmpty()
                    ? $siteSeo->ogImage()->toFiles()->first()?->filename()
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

        // Pages use flat fields
        foreach ($pages as $page) {
            $legacy = [];
            $current = [];

            // Check for common legacy SEO field names
            // Meta Title variations
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->metatitle()->value();
                $current['metaTitle'] = $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null;
            } elseif ($page->meta_title()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->meta_title()->value();
                $current['metaTitle'] = $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null;
            }

            // Meta Description variations
            if ($page->metadescription()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->metadescription()->value();
                $current['metaDescription'] = $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null;
            } elseif ($page->meta_description()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->meta_description()->value();
                $current['metaDescription'] = $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null;
            }

            // OG Image
            if ($page->ogimage()->isNotEmpty()) {
                $files = $page->ogimage()->toFiles();
                if ($files->count() > 0) {
                    $legacy['ogImage'] = $files->first()->filename();
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

            if ($isSite) {
                // Site uses blocks
                $seoData = MetaKitController::getSiteSeoData($page);
                $seoArray = MetaKitController::seoDataToArray($seoData);

                // Migrate legacy fields - Meta Title variations
                if ($page->metatitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                    $seoArray['metaTitle'] = $page->metatitle()->value();
                    $converted[] = 'metaTitle (from metatitle)';
                } elseif ($page->meta_title()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                    $seoArray['metaTitle'] = $page->meta_title()->value();
                    $converted[] = 'metaTitle (from Meta-title)';
                }

                // Migrate legacy fields - Meta Description variations
                if ($page->metadescription()->isNotEmpty() && empty($seoArray['metaDescription'])) {
                    $seoArray['metaDescription'] = $page->metadescription()->value();
                    $converted[] = 'metaDescription (from metadescription)';
                } elseif ($page->meta_description()->isNotEmpty() && empty($seoArray['metaDescription'])) {
                    $seoArray['metaDescription'] = $page->meta_description()->value();
                    $converted[] = 'metaDescription (from Meta-description)';
                }

                // Migrate legacy fields - OG Image
                if ($page->ogimage()->isNotEmpty() && empty($seoArray['ogImage'])) {
                    $files = $page->ogimage()->toFiles();
                    if ($files && $files->count() > 0) {
                        $seoArray = self::convertUUIDObjectsToStrings($files, $seoArray);
                        $converted[] = 'ogImage (from ogimage)';
                    }
                }

                // Build update array for site (uses blocks)
                if (count($converted) > 0) {
                    $seoBlock = [
                        [
                            'content' => $seoArray,
                            'id' => 'site-seo-settings',
                            'isHidden' => false,
                            'type' => 'mk-site-seo'
                        ]
                    ];
                    $updateData['metaKitSeo'] = $seoBlock;
                }
            } else {
                // Pages use flat fields
                // Migrate legacy fields - Meta Title variations
                if ($page->metatitle()->isNotEmpty() && $page->metaTitle()->isEmpty()) {
                    $updateData['metaTitle'] = $page->metatitle()->value();
                    $converted[] = 'metaTitle (from metatitle)';
                } elseif ($page->meta_title()->isNotEmpty() && $page->metaTitle()->isEmpty()) {
                    $updateData['metaTitle'] = $page->meta_title()->value();
                    $converted[] = 'metaTitle (from meta_title)';
                }

                // Migrate legacy fields - Meta Description variations
                if ($page->metadescription()->isNotEmpty() && $page->metaDescription()->isEmpty()) {
                    $updateData['metaDescription'] = $page->metadescription()->value();
                    $converted[] = 'metaDescription (from metadescription)';
                } elseif ($page->meta_description()->isNotEmpty() && $page->metaDescription()->isEmpty()) {
                    $updateData['metaDescription'] = $page->meta_description()->value();
                    $converted[] = 'metaDescription (from meta_description)';
                }

                // Migrate legacy fields - OG Image
                if ($page->ogimage()->isNotEmpty() && $page->ogImage()->isEmpty()) {
                    $files = $page->ogimage()->toFiles();
                    if ($files && $files->count() > 0) {
                        $uuids = [];
                        foreach ($files as $file) {
                            if ($uuid = $file->uuid()) {
                                $uuids[] = $uuid->toString();
                            }
                        }
                        $updateData['ogImage'] = $uuids;
                        $converted[] = 'ogImage (from ogimage)';
                    }
                } elseif ($page->og_image()->isNotEmpty() && $page->ogImage()->isEmpty()) {
                    $files = $page->og_image()->toFiles();
                    if ($files && $files->count() > 0) {
                        $uuids = [];
                        foreach ($files as $file) {
                            if ($uuid = $file->uuid()) {
                                $uuids[] = $uuid->toString();
                            }
                        }
                        $updateData['ogImage'] = $uuids;
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
     * @param $files
     * @param mixed $seoArray
     * @return mixed
     */
    public static function convertUUIDObjectsToStrings($files, mixed $seoArray): mixed
    {
        // Convert UUID objects to strings
        $uuids = [];
        foreach ($files as $file) {
            if ($uuid = $file->uuid()) {
                $uuids[] = $uuid->toString();
            }
        }
        $seoArray['ogImage'] = $uuids;
        return $seoArray;
    }
}
