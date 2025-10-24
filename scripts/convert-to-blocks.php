#!/usr/bin/env php
<?php
/**
 * Convert SEO object fields to blocks format
 * Run: php site/plugins/kirby-seo-ai/scripts/convert-to-blocks.php
 */

require __DIR__ . '/../../../../vendor/autoload.php';

$kirby = new Kirby([
    'roots' => [
        'index' => __DIR__ . '/../../../../public',
        'base' => __DIR__ . '/../../../..',
        'content' => __DIR__ . '/../../../../content',
    ]
]);

$converted = 0;
$skipped = 0;
$errors = 0;

// Convert site
echo "Converting site SEO data...\n";
$site = $kirby->site();

try {
    $updates = [];
    $needsUpdate = false;

    // Check and convert SEO field
    $seoField = $site->metaKitSeo();
    if ($seoField->isNotEmpty()) {
        // Check if it's already in blocks format
        $rawValue = $seoField->value();
        $isBlocks = is_string($rawValue) && str_starts_with(trim($rawValue), '[');

        if (!$isBlocks) {
            // Convert from object to blocks
            $obj = $seoField->toObject();
            if ($obj) {
                $updates['seo'] = [
                    [
                        'content' => [
                            'appendSiteName' => $obj->appendsitename()->toBool(),
                            'titleSeparator' => $obj->titleseparator()->or('|')->value(),
                            'metaTitle' => $obj->metatitle()->value(),
                            'metaDescription' => $obj->metadescription()->value(),
                            'metaKeywords' => $obj->metakeywords()->value(),
                            'ogImage' => $obj->ogimage()->isNotEmpty() ? $obj->ogimage()->toFiles()->pluck('uuid')->toArray() : []
                        ],
                        'id' => 'seo-settings',
                        'isHidden' => false,
                        'type' => 'seo-settings'
                    ]
                ];
                $needsUpdate = true;
            }
        }
    }

    // Check and convert OpenRouter field
    $openrouterField = $site->openrouter();
    if ($openrouterField->isNotEmpty()) {
        $rawValue = $openrouterField->value();
        $isBlocks = is_string($rawValue) && str_starts_with(trim($rawValue), '[');

        if (!$isBlocks) {
            $obj = $openrouterField->toObject();
            if ($obj) {
                $updates['openrouter'] = [
                    [
                        'content' => [
                            'apiKey' => $obj->apikey()->value(),
                            'model' => $obj->model()->or('meta-llama/llama-3.2-3b-instruct:free')->value(),
                            'temperature' => $obj->temperature()->or('0.7')->value()
                        ],
                        'id' => 'openrouter-settings',
                        'isHidden' => false,
                        'type' => 'openrouter'
                    ]
                ];
                $needsUpdate = true;
            }
        }
    }

    // Check and convert Sitemap field
    $sitemapField = $site->sitemap();
    if ($sitemapField->isNotEmpty()) {
        $rawValue = $sitemapField->value();
        $isBlocks = is_string($rawValue) && str_starts_with(trim($rawValue), '[');

        if (!$isBlocks) {
            $obj = $sitemapField->toObject();
            if ($obj) {
                $excludePages = [];
                if ($obj->exclude()->isNotEmpty()) {
                    $excludePages = $obj->exclude()->toPages()->pluck('uuid')->toArray();
                }

                $updates['sitemap'] = [
                    [
                        'content' => [
                            'exclude' => $excludePages,
                            'priorityHome' => $obj->priorityhome()->or('1.0')->value(),
                            'priorityDefault' => $obj->prioritydefault()->or('0.8')->value()
                        ],
                        'id' => 'sitemap-settings',
                        'isHidden' => false,
                        'type' => 'sitemap'
                    ]
                ];
                $needsUpdate = true;
            }
        }
    }

    if ($needsUpdate) {
        $kirby->impersonate('kirby');
        $site->update($updates);
        echo "✓ Site converted\n";
        $converted++;
    } else {
        echo "- Site already in blocks format\n";
        $skipped++;
    }
} catch (Exception $e) {
    echo "✗ Site conversion failed: " . $e->getMessage() . "\n";
    $errors++;
}

// Convert all pages
echo "\nConverting pages...\n";
$pages = $kirby->site()->index();

foreach ($pages as $page) {
    $seoField = $page->metaKitSeo();

    if ($seoField->isEmpty()) {
        continue;
    }

    // Check if already in blocks format by checking the raw value
    $rawValue = $seoField->value();
    $isBlocks = false;

    if (is_string($rawValue)) {
        $trimmed = trim($rawValue);
        // Blocks format starts with [ (JSON array)
        // Object format starts with field names like "metatitle:"
        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $isBlocks = true;
            }
        }
    }

    if ($isBlocks) {
        $skipped++;
        continue;
    }

    // Convert from object to blocks
    $obj = $seoField->toObject();
    if (!$obj) {
        $skipped++;
        continue;
    }

    try {
        $ogImageUuids = [];
        if ($obj->ogimage()->isNotEmpty()) {
            $files = $obj->ogimage()->toFiles();
            if ($files->count() > 0) {
                $ogImageUuids = $files->pluck('uuid')->toArray();
            }
        }

        $seoBlock = [
            [
                'content' => [
                    'metaTitle' => $obj->metatitle()->value(),
                    'metaDescription' => $obj->metadescription()->value(),
                    'metaKeywords' => $obj->metakeywords()->value(),
                    'canonicalUrl' => $obj->canonicalurl()->value(),
                    'noIndex' => $obj->noindex()->toBool(),
                    'ogTitle' => $obj->ogtitle()->value(),
                    'ogDescription' => $obj->ogdescription()->value(),
                    'ogImage' => $ogImageUuids
                ],
                'id' => 'seo-metadata',
                'isHidden' => false,
                'type' => 'seo'
            ]
        ];

        $kirby->impersonate('kirby');
        $page->update(['seo' => $seoBlock], $kirby->language()?->code());

        echo "✓ {$page->id()}\n";
        $converted++;
    } catch (Exception $e) {
        echo "✗ {$page->id()}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n========================================\n";
echo "Conversion complete!\n";
echo "Converted: {$converted}\n";
echo "Skipped (already blocks): {$skipped}\n";
echo "Errors: {$errors}\n";
echo "========================================\n";
