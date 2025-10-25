<?php

namespace TearoomOne;

use Kirby\Cms\Page;

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

            // Migrate legacy fields - OG Image
            if ($page->ogimage()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $seoArray['ogImage'] = $page->ogimage()->toFiles();
                $converted[] = 'ogImage';
            } elseif ($page->og_image()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $seoArray['ogImage'] = $page->og_image()->toFiles();
                $converted[] = 'ogImage';
            } elseif ($page->meta_image()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $seoArray['ogImage'] = $page->meta_image()->toFiles();
                $converted[] = 'ogImage';
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

            // List of all legacy SEO fields to remove
            $legacyFields = [
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

            // Build update array - only update metaKitSeo if it already exists AND we have conversions
            $updateData = [];
            if (!empty($converted)) {
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

            // Check which legacy fields actually exist and mark them for removal
            $contentFields = $content->fields();
            $fieldsToDelete = [];

            foreach ($legacyFields as $field) {
                // Check if field exists in content
                if (array_key_exists($field, $contentFields)) {
                    $fieldsToDelete[] = $field;
                }
            }

            // Flip keys and values and set new values to null
            if (!empty($fieldsToDelete)) {
                $deleteData = array_map(fn ($value) => null, array_flip($fieldsToDelete));
                $updateData = array_merge($updateData, $deleteData);
            }

            // Only update if we have something to do
            if (!empty($updateData)) {
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
}
