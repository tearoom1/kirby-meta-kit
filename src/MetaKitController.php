<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;

class MetaKitController
{
    public static function getPages(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $result = [];

        foreach ($pages as $page) {
            $seoData = $page->seo()->toObject();

            $result[] = [
                'id' => $page->id(),
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => $page->intendedTemplate()->name(),
                'hasMetaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty(),
                'hasMetaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty(),
                'hasOgImage' => $seoData && $seoData->ogImage()->isNotEmpty(),
                'noIndex' => $seoData && $seoData->noIndex()->toBool(),
                'metaTitleLength' => $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? mb_strlen($seoData->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? mb_strlen($seoData->metaDescription()->value())
                    : 0,
            ];
        }

        return $result;
    }

    public static function generateDescription(string $pageId): array
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
            $metaKit = new MetaKit($kirby);
            $languageCode = $kirby->language()?->code() ?? 'en';

            // Get page content for generation
            $content = $page->text()->or($page->title())->value();
            $description = $metaKit->generateDescription($content, ['language' => $languageCode]);

            if ($description) {
                // Update page
                $seoData = $page->seo()->toObject();
                $seoArray = $seoData ? $seoData->toArray() : [];
                $seoArray['metaDescription'] = $description;

                $page->update(['seo' => $seoArray], $languageCode);

                return [
                    'status' => 'success',
                    'message' => 'Description generated successfully',
                    'description' => $description
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to generate description'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function generateAllDescriptions(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $generated = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($pages as $page) {
            $seoData = $page->seo()->toObject();

            // Skip if already has description
            if ($seoData && $seoData->metaDescription()->isNotEmpty()) {
                $skipped++;
                continue;
            }

            $result = self::generateDescription($page->id());

            if ($result['status'] === 'success') {
                $generated++;
            } else {
                $failed++;
            }
        }

        return [
            'status' => 'success',
            'message' => "Generated {$generated} descriptions, skipped {$skipped}, failed {$failed}",
            'generated' => $generated,
            'skipped' => $skipped,
            'failed' => $failed
        ];
    }

    public static function detectLegacyMetadata(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $found = [];

        foreach ($pages as $page) {
            $legacy = [];
            $seoData = $page->seo()->toObject();
            $current = [];

            // Check for common legacy SEO field names
            // Meta Title variations
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->metatitle()->value();
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
            $seoData = $page->seo()->toObject();
            $seoArray = $seoData ? $seoData->toArray() : [];
            $converted = [];

            // Migrate legacy fields - Meta Title variations
            if ($page->metatitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->metatitle()->value();
                $converted[] = 'metaTitle (from metatitle)';
            } elseif ($page->Metatitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->Metatitle()->value();
                $converted[] = 'metaTitle (from Metatitle)';
            } elseif ($page->seotitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->seotitle()->value();
                $converted[] = 'metaTitle (from seotitle)';
            } elseif ($page->customtitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->customtitle()->value();
                $converted[] = 'metaTitle (from customtitle)';
            }

            // Migrate legacy fields - Meta Description variations
            if ($page->metadescription()->isNotEmpty() && empty($seoArray['metaDescription'])) {
                $seoArray['metaDescription'] = $page->metadescription()->value();
                $converted[] = 'metaDescription (from metadescription)';
            } elseif ($page->seodescription()->isNotEmpty() && empty($seoArray['metaDescription'])) {
                $seoArray['metaDescription'] = $page->seodescription()->value();
                $converted[] = 'metaDescription (from seodescription)';
            }

            // Migrate legacy fields - OG Image
            if ($page->ogimage()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $seoArray['ogImage'] = $page->ogimage()->toFiles();
                $converted[] = 'ogImage';
            }

            if (!empty($converted)) {
                $languageCode = $kirby->language()?->code();
                $page->update(['seo' => $seoArray], $languageCode);

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

    public static function applySingleField(string $pageId, string $fieldName, $value): array
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
            $seoData = $page->seo()->toObject();
            $seoArray = $seoData ? $seoData->toArray() : [];

            // Update the specific field
            $seoArray[$fieldName] = $value;

            $languageCode = $kirby->language()?->code();
            $page->update(['seo' => $seoArray], $languageCode);

            return [
                'status' => 'success',
                'message' => 'Field updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
