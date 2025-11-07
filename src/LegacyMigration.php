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

    public static function detectLegacyMetadata(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $found = [];

        foreach ($pages as $page) {
            $legacy = [];
            $seoData = MetaKitController::getSeoData($page->metaKitSeo());
            $current = [];

            // Check for common legacy SEO field names
            // Meta Title variations
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->metatitle()->value();
                $current['metaTitle'] = $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? $seoData->metaTitle()->value()
                    : null;
            } elseif ($page->meta_title()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->meta_title()->value();
                $current['metaTitle'] = $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? $seoData->metaTitle()->value()
                    : null;
            }

            // Meta Description variations
            if ($page->metadescription()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->metadescription()->value();
                $current['metaDescription'] = $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? $seoData->metaDescription()->value()
                    : null;
            } elseif ($page->meta_description()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->meta_description()->value();
                $current['metaDescription'] = $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? $seoData->metaDescription()->value()
                    : null;
            }

            // OG Image
            if ($page->ogimage()->isNotEmpty()) {
                $files = $page->ogimage()->toFiles();
                if ($files->count() > 0) {
                    $legacy['ogImage'] = $files->first()->filename();
                    $current['ogImage'] = $seoData && $seoData->ogImage()->isNotEmpty()
                        ? $seoData->ogImage()->toFiles()->first()?->filename()
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
        $page = $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        try {
            $seoData = MetaKitController::getSeoData($page->metaKitSeo());
            $seoArray = MetaKitController::seoDataToArray($seoData);
            $converted = [];

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

            // Migrate legacy fields - OG Image (needs to be array of UUID strings)
            if ($page->ogimage()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $files = $page->ogimage()->toFiles();
                if ($files && $files->count() > 0) {
                    $seoArray = self::convertUUIDObjectsToStrings($files, $seoArray);
                    $converted[] = 'ogImage (from ogimage)';
                }
            } elseif ($page->og_image()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $files = $page->og_image()->toFiles();
                if ($files && $files->count() > 0) {
                    // Convert UUID objects to strings
                    $seoArray = self::convertUUIDObjectsToStrings($files, $seoArray);
                    $converted[] = 'ogImage (from og_image)';
                }
            } elseif ($page->meta_image()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $files = $page->meta_image()->toFiles();
                if ($files && $files->count() > 0) {
                    // Convert UUID objects to strings
                    $seoArray = self::convertUUIDObjectsToStrings($files, $seoArray);
                    $converted[] = 'ogImage (from meta_image)';
                }
            }

            $languageCode = $kirby->language()?->code();

            // Check if translation file exists for this language
            $content = $page->content($languageCode);
            if (!$content->exists()) {
                return [
                    'status' => 'info',
                    'message' => 'Translation file does not exist for language: ' . $languageCode
                ];
            }

            // Build update array - only update metaKitSeo if it already exists AND we have conversions
            $updateData = [];
            if (count($converted) > 0) {
                $seoBlock = [
                    [
                        'content' => $seoArray,
                        'id' => 'seo-metadata',
                        'isHidden' => false,
                        'type' => 'mk-page-seo'
                    ]
                ];
                $updateData['metaKitSeo'] = $seoBlock;
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
                'robots_noimageindex', 'robots_nosnippet'
            ];


            foreach ($fieldsToDelete as $fieldToDelete) {
                $updateData[$fieldToDelete] = null;
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
