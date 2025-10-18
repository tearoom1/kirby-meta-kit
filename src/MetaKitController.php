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

            // Check for common legacy SEO field names
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->metatitle()->value();
            }
            if ($page->metadescription()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->metadescription()->value();
            }
            if ($page->ogimage()->isNotEmpty()) {
                $legacy['ogImage'] = $page->ogimage()->toFiles();
            }
            if ($page->customtitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->customtitle()->value();
            }

            if (!empty($legacy)) {
                $found[] = [
                    'id' => $page->id(),
                    'title' => $page->title()->value(),
                    'fields' => $legacy
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

            // Migrate legacy fields
            if ($page->metatitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->metatitle()->value();
                $converted[] = 'metaTitle';
            }
            if ($page->metadescription()->isNotEmpty() && empty($seoArray['metaDescription'])) {
                $seoArray['metaDescription'] = $page->metadescription()->value();
                $converted[] = 'metaDescription';
            }
            if ($page->customtitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->customtitle()->value();
                $converted[] = 'metaTitle (from customtitle)';
            }
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
}
