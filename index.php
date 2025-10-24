<?php

use TearoomOne\MetaKit;
use Kirby\Http\Response;

if (!option('tearoom1.meta-kit.enabled', true)) {
    return;
}

@include_once __DIR__ . '/vendor/autoload.php';

load([
    'TearoomOne\MetaKit' => 'src/MetaKit.php',
    'TearoomOne\Sitemap' => 'src/Sitemap.php',
    'TearoomOne\MetaHelper' => 'src/MetaHelper.php',
    'TearoomOne\MetaKitController' => 'src/MetaKitController.php',
], __DIR__);

Kirby::plugin('tearoom1/meta-kit', [
    'options' => [
        'api.key' => null,
        'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
        'api.model' => '',
        'api.temperature' => 0.7,
        'maxDescriptionLength' => 160,
        'sitemap.include' => 'all',
        'sitemap.exclude' => ['error'],
        'autoGenerate' => false,
    ],
    'blueprints' => [
        'meta-kit/site' => __DIR__ . '/blueprints/site.yml',
        'meta-kit/page' => __DIR__ . '/blueprints/page.yml',
        'meta-kit/fields/og-image' => __DIR__ . '/blueprints/fields/og-image.php',
        'blocks/mk-page-seo' => __DIR__ . '/blueprints/blocks/mk-page-seo.yml',
        'blocks/mk-site-seo' => __DIR__ . '/blueprints/blocks/mk-site-seo.yml',
        'blocks/mk-openrouter' => __DIR__ . '/blueprints/blocks/mk-openrouter.yml',
        'blocks/mk-sitemap' => __DIR__ . '/blueprints/blocks/mk-sitemap.yml',
    ],
    'sections' => [
        'seo-preview' => __DIR__ . '/sections/preview.php',
    ],
    'fields' => [
        'meta-kit-generator' => [],
    ],
    'snippets' => [
        'meta-kit/seo' => __DIR__ . '/snippets/seo.php',
    ],
    'areas' => [
        'meta-kit' => require __DIR__ . '/src/areas/meta-kit.php',
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'meta-kit/generate',
                'method' => 'POST',
                'auth' => true,
                'action' => require __DIR__ . '/src/api/generate.php'
            ],
            [
                'pattern' => 'meta-kit/convert-all-to-blocks',
                'method' => 'POST',
                'auth' => true,
                'action' => function () {
                    return \TearoomOne\MetaKitController::convertAllLegacyFields();
                }
            ],
            [
                'pattern' => 'meta-kit/pages',
                'method' => 'GET',
                'auth' => true,
                'action' => function () {
                    $data = \TearoomOne\MetaKitController::getPages();
                    return [
                        'status' => 'success',
                        'data' => $data['pages'],
                        'language' => $data['language'],
                        'languages' => $data['languages']
                    ];
                }
            ],
            [
                'pattern' => 'meta-kit/generate-description',
                'method' => 'POST',
                'auth' => true,
                'action' => function () {
                    $pageId = get('pageId');
                    return \TearoomOne\MetaKitController::generateDescription($pageId);
                }
            ],
            [
                'pattern' => 'meta-kit/generate-all',
                'method' => 'POST',
                'auth' => true,
                'action' => function () {
                    return \TearoomOne\MetaKitController::generateAllDescriptions();
                }
            ],
            [
                'pattern' => 'meta-kit/detect-legacy',
                'method' => 'GET',
                'auth' => true,
                'action' => function () {
                    return \TearoomOne\MetaKitController::detectLegacyMetadata();
                }
            ],
            [
                'pattern' => 'meta-kit/convert-legacy',
                'method' => 'POST',
                'auth' => true,
                'action' => function () {
                    $pageId = get('pageId');
                    return \TearoomOne\MetaKitController::convertLegacyMetadata($pageId);
                }
            ],
            [
                'pattern' => 'meta-kit/apply-single-field',
                'method' => 'POST',
                'auth' => true,
                'action' => function () {
                    $pageId = get('pageId');
                    $fieldName = get('fieldName');
                    $value = get('value');
                    return \TearoomOne\MetaKitController::applySingleField($pageId, $fieldName, $value);
                }
            ],
            [
                'pattern' => 'meta-kit/pages-with-content',
                'method' => 'GET',
                'auth' => true,
                'action' => function () {
                    return \TearoomOne\MetaKitController::getPagesWithContent();
                }
            ],
            [
                'pattern' => 'meta-kit/single-page',
                'method' => 'GET',
                'auth' => true,
                'action' => function () {
                    $pageId = get('pageId');
                    return \TearoomOne\MetaKitController::getSinglePage($pageId);
                }
            ],
            [
                'pattern' => 'meta-kit/generate-field',
                'method' => 'POST',
                'auth' => true,
                'action' => function () {
                    $pageId = get('pageId');
                    $fieldName = get('fieldName');
                    $language = get('language');
                    return \TearoomOne\MetaKitController::generateField($pageId, $fieldName, $language);
                }
            ]
        ]
    ],
    'routes' => [
        [
            'pattern' => 'sitemap.xsl',
            'action' => require __DIR__ . '/src/routes/sitemap-xsl.php'
        ],
        [
            'pattern' => 'sitemap.xml',
            'action' => require __DIR__ . '/src/routes/sitemap.php'
        ]
    ],
    'pageMethods' => [
        'generateSeoDescription' => function (?string $content = null, ?string $languageCode = null) {
            $metaKit = new MetaKit(kirby());
            $languageCode = $languageCode ?? kirby()->language()?->code() ?? 'en';
            $content = $content ?? $this->text()->toString();

            if (empty($content)) {
                return null;
            }

            return $metaKit->generateDescription($content, ['language' => $languageCode]);
        }
    ],
    'fieldMethods' => [
        'toSeoDescription' => function ($field) {
            $metaKit = new MetaKit(kirby());
            $languageCode = kirby()->language()?->code() ?? 'en';
            return $metaKit->generateDescription($field->value(), ['language' => $languageCode]);
        }
    ],
    'hooks' => [
        'system.loadPlugins:after' => function () {
            // Initialize site SEO objects on first load if they don't exist
            $site = site();
            $needsUpdate = false;
            $updates = [];

            // Check if objects need initialization
            if ($site->metaKitSeo()->isEmpty()) {
                $updates['metaKitSeo'] = [[
                    'content' => [
                        'appendSiteName' => true,
                        'titleSeparator' => '|',
                        'metaTitle' => '',
                        'metaDescription' => '',
                        'metaKeywords' => '',
                        'ogImage' => []
                    ],
                    'id' => 'site-seo-settings',
                    'isHidden' => false,
                    'type' => 'mk-site-seo'
                ]];
                $needsUpdate = true;
            }

            if ($site->metaKitOpenrouter()->isEmpty()) {
                $updates['metaKitOpenrouter'] = [[
                    'content' => [
                        'apiKey' => '',
                        'model' => 'meta-llama/llama-3.2-3b-instruct:free',
                        'temperature' => 0.7
                    ],
                    'id' => 'openrouter-settings',
                    'isHidden' => false,
                    'type' => 'mk-openrouter'
                ]];
                $needsUpdate = true;
            }

            if ($site->metaKitSitemap()->isEmpty()) {
                $updates['metaKitSitemap'] = [[
                    'content' => [
                        'exclude' => [],
                        'priorityHome' => 1.0,
                        'priorityDefault' => 0.8
                    ],
                    'id' => 'sitemap-settings',
                    'isHidden' => false,
                    'type' => 'mk-sitemap'
                ]];
                $needsUpdate = true;
            }

            // Only update if needed and not already being updated
            if ($needsUpdate && !defined('KIRBY_META_KIT_INITIALIZING')) {
                define('KIRBY_META_KIT_INITIALIZING', true);
                try {
                    kirby()->impersonate('kirby');
                    $site->update($updates);
                } catch (\Exception $e) {
                    // Silently fail - site might be read-only or in a context where updates aren't allowed
                }
            }
        },
        'page.update:after' => function ($newPage, $oldPage) {
            // Auto-generate description if enabled and field is empty
            $autoGenerate = option('tearoom1.meta-kit.autoGenerate', false);

            if (!$autoGenerate || $newPage->intendedTemplate()->name() === 'error') {
                return;
            }

            try {
                // Check if SEO object exists and if metadescription is empty
                $seoData = $newPage->metaKitSeo()->toObject();
                if (!$seoData || $seoData->metadescription()->isNotEmpty()) {
                    return;
                }

                $content = $newPage->text()->toString();
                if (!empty($content)) {
                    $metaKit = new MetaKit(kirby());
                    $languageCode = kirby()->language()?->code() ?? 'en';
                    $description = $metaKit->generateDescription($content, ['language' => $languageCode]);

                    if ($description) {
                        // Get existing SEO data and update metadescription
                        $existingSeo = $newPage->metaKitSeo()->yaml();
                        $existingSeo['metadescription'] = $description;

                        $newPage->update([
                            'seo' => $existingSeo
                        ], kirby()->language()?->code());
                    }
                }
            } catch (Exception $e) {
                // Silently fail - don't break the save operation
                kirbylog('Meta Kit auto-generate error: ' . $e->getMessage());
            }
        }
    ]
]);
