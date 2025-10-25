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
            }

            if (!empty($converted)) {
                $languageCode = $kirby->language()?->code();
                $seoBlock = [
                    [
                        'content' => $seoArray,
                        'id' => 'seo-metadata',
                        'isHidden' => false,
                        'type' => 'mk-page-seo'
                    ]
                ];
                $page->update(['metaKitSeo' => $seoBlock], $languageCode);

                return [
                    'status' => 'success',
                    'message' => 'Converted: ' . implode(', ', $converted),
                    'converted' => $converted
                ];
            } else {
                return [
                    'status' => 'info',
                    'message' => 'No legacy fields found or already converted'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
