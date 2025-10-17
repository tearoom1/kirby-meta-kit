<?php

use TearoomOne\MetaKit;
use Kirby\Http\Response;

@include_once __DIR__ . '/vendor/autoload.php';

load([
    'TearoomOne\MetaKit' => 'src/MetaKit.php',
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
    ],
    'sections' => [
        'seo-preview' => __DIR__ . '/sections/preview.php',
    ],
    'fields' => [
        'meta-kit-generator' => [],
    ],
    'snippets' => [
        'meta-kit/seo' => __DIR__ . '/snippets/seo.php',
        // Legacy snippets (deprecated, use meta-kit/seo instead)
        'seo/meta' => __DIR__ . '/snippets/meta.php',
        'seo/opengraph' => __DIR__ . '/snippets/opengraph.php',
        'seo/schema' => __DIR__ . '/snippets/schema.php',
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'meta-kit/generate',
                'method' => 'POST',
                'auth' => true,
                'action' => require __DIR__ . '/src/api/generate.php'
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
        'generateSeoDescription' => function (string $content = null, string $languageCode = null) {
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
        'page.update:after' => function ($newPage, $oldPage) {
            // Auto-generate description if enabled and field is empty
            $autoGenerate = option('tearoom1.meta-kit.autoGenerate', false);

            if (!$autoGenerate || $newPage->intendedTemplate()->name() === 'error') {
                return;
            }

            try {
                // Check if SEO object exists and if metadescription is empty
                $seoData = $newPage->seo()->toObject();
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
                        $existingSeo = $newPage->seo()->yaml();
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
