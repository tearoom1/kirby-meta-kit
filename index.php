<?php

use Kirby\Cms\App;

if (!option('tearoom1.meta-kit.enabled', true)) {
    return;
}

@include_once __DIR__ . '/vendor/autoload.php';

$classes = [
    'TearoomOne\ConfigHelper' => 'classes/ConfigHelper.php',
    'TearoomOne\MetaHelper' => 'classes/MetaHelper.php',
    'TearoomOne\MetaKit' => 'classes/MetaKit.php',
    'TearoomOne\Sitemap' => 'classes/Sitemap.php',
    'TearoomOne\Robots' => 'classes/Robots.php',
    'TearoomOne\MetaKitController' => 'classes/MetaKitController.php',
    'TearoomOne\PageDataBuilder' => 'classes/PageDataBuilder.php',
    'TearoomOne\ApiResponse' => 'classes/ApiResponse.php',
    'TearoomOne\SeoAudit' => 'classes/SeoAudit.php',
    'TearoomOne\SeoReview' => 'classes/SeoReview.php',
];

load($classes, __DIR__);


App::plugin(
    name: 'tearoom1/meta-kit',
    extends: [
        'options' => [
            'cache' => [
                'performer' => [
                    'active' => true
                ]
            ],
            'allowedRoles' => [], // Additional Kirby roles allowed to use the plugin (admins always allowed). Example: ['editor', 'client']
            'ai.enabled' => true,
            'review.enabled' => false,
            'ai.tone' => 'formal',
            'api.key' => null,
            'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'api.model' => 'google/gemma-4-31b-it:free',
            'api.temperature' => 0.7,
            'maxDescriptionLength' => 160,
            'validation' => [
                'ranges' => [
                    'title' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
                    'ogTitle' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
                    'description' => ['optimal' => ['min' => 140, 'max' => 160], 'warning' => ['min' => 126, 'max' => 176]],
                    'ogDescription' => ['optimal' => ['min' => 150, 'max' => 250], 'warning' => ['min' => 135, 'max' => 300]],
                ],
                'slug' => [
                    'depth' => [
                        'optimal' => ['min' => 0, 'max' => 2],
                        'warning' => ['min' => 0, 'max' => 3],
                    ],
                    'words' => [
                        'optimal' => ['min' => 1, 'max' => 8],
                        'warning' => ['min' => 1, 'max' => 10],
                    ],
                    'length' => [
                        'optimal' => ['min' => 3, 'max' => 60],
                        'warning' => ['min' => 2, 'max' => 70],
                    ],
                    'wordLength' => [
                        'optimal' => ['min' => 1, 'max' => 15],
                        'warning' => ['min' => 1, 'max' => 20],
                    ],
                ],
                'templates' => []
            ],
            'excludeTemplates' => [],
            'excludeStatus' => [],
            'sitemap.exclude' => ['error'],
            'autoGenerate' => false,
        ],
        'blueprints' => [
            'meta-kit/site' => __DIR__ . '/blueprints/site.yml',
            'meta-kit/page' => __DIR__ . '/blueprints/page.yml',
            // Flat field groups for SEO
            'fields/meta-group' => __DIR__ . '/blueprints/fields/meta-group.yml',
            'fields/seo-group' => __DIR__ . '/blueprints/fields/seo-group.yml',
            'fields/site-seo-group' => __DIR__ . '/blueprints/fields/site-seo-group.yml',
            'meta-kit/fields/og-image' => __DIR__ . '/blueprints/fields/og-image.php',
            // Blocks for other settings (OpenRouter, Sitemap, Robots)
            'blocks/mk-openrouter' => __DIR__ . '/blueprints/blocks/mk-openrouter.yml',
            'blocks/mk-sitemap' => __DIR__ . '/blueprints/blocks/mk-sitemap.yml',
            'blocks/mk-robots' => __DIR__ . '/blueprints/blocks/mk-robots.yml',
        ],
        'sections' => [
            'seo-preview' => __DIR__ . '/sections/preview.php',
        ],
        'fields' => [
            'mk-title' => __DIR__ . '/fields/mk-title/index.php',
            'mk-description' => __DIR__ . '/fields/mk-description/index.php',
            'mk-slug-info' => __DIR__ . '/fields/mk-slug-info/index.php',
            'mk-review' => __DIR__ . '/fields/mk-review/index.php',
        ],
        'snippets' => [
            'meta-kit/seo' => __DIR__ . '/snippets/seo.php',
        ],
        'areas' => [
            'meta-kit' => require __DIR__ . '/src/areas/meta-kit.php',
        ],
        'hooks' => require __DIR__ . '/src/hooks.php',
        'api' => [
            'routes' => require __DIR__ . '/src/api/routes.php',
        ],
        'routes' => require __DIR__ . '/src/routes/routes.php',
        'pageMethods' => [
            'generateSeoTitle' => function (?string $content = null, ?string $languageCode = null) {
                if (!TearoomOne\MetaKit::isAiEnabled()) {
                    return null;
                }

                $metaKit = new TearoomOne\MetaKit(kirby());
                $languageCode = $languageCode ?? kirby()->language()?->code() ?? 'en';
                $content = $content ?? $this->text()->toString();

                if (empty($content)) {
                    return null;
                }

                return $metaKit->generateTitle($content, ['language' => $languageCode]);
            },
            'generateSeoDescription' => function (?string $content = null, ?string $languageCode = null) {
                if (!TearoomOne\MetaKit::isAiEnabled()) {
                    return null;
                }

                $metaKit = new TearoomOne\MetaKit(kirby());
                $languageCode = $languageCode ?? kirby()->language()?->code() ?? 'en';
                $content = $content ?? $this->text()->toString();

                if (empty($content)) {
                    return null;
                }

                return $metaKit->generateDescription($content, ['language' => $languageCode]);
            }
        ],
        'fieldMethods' => [
            'toSeoTitle' => function ($field) {
                if (!TearoomOne\MetaKit::isAiEnabled()) {
                    return null;
                }

                $metaKit = new TearoomOne\MetaKit(kirby());
                $languageCode = kirby()->language()?->code() ?? 'en';
                return $metaKit->generateTitle($field->value(), ['language' => $languageCode]);
            },
            'toSeoDescription' => function ($field) {
                if (!TearoomOne\MetaKit::isAiEnabled()) {
                    return null;
                }

                $metaKit = new TearoomOne\MetaKit(kirby());
                $languageCode = kirby()->language()?->code() ?? 'en';
                return $metaKit->generateDescription($field->value(), ['language' => $languageCode]);
            }
        ],
    ]);
