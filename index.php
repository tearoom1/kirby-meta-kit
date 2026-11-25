<?php

use Kirby\Blueprint\Normalizer;
use Kirby\Cms\App;
use TearoomOne\Fields\DescriptionField;
use TearoomOne\Fields\PreviewField;
use TearoomOne\Fields\ReviewField;
use TearoomOne\Fields\SlugInfoField;
use TearoomOne\Fields\TitleField;

if (!option('tearoom1.meta-kit.enabled', true)) {
    return;
}

// Own classes are listed below; the plugin's vendor/ folder is not loaded,
// as in a development checkout it holds a second copy of Kirby

$classes = [
    'TearoomOne\ConfigHelper' => 'classes/ConfigHelper.php',
    'TearoomOne\MetaHelper' => 'classes/MetaHelper.php',
    'TearoomOne\MetaKit' => 'classes/MetaKit.php',
    'TearoomOne\Sitemap' => 'classes/Sitemap.php',
    'TearoomOne\Robots' => 'classes/Robots.php',
    'TearoomOne\LlmsTxt' => 'classes/LlmsTxt.php',
    'TearoomOne\Redirects' => 'classes/Redirects.php',
    'TearoomOne\Texts' => 'classes/Texts.php',
    'TearoomOne\MetaKitController' => 'classes/MetaKitController.php',
    'TearoomOne\PageDataBuilder' => 'classes/PageDataBuilder.php',
    'TearoomOne\ApiResponse' => 'classes/ApiResponse.php',
    'TearoomOne\SeoReview' => 'classes/SeoReview.php',
    'TearoomOne\Fields\FieldContext' => 'classes/Fields/FieldContext.php',
    'TearoomOne\Fields\TitleField' => 'classes/Fields/TitleField.php',
    'TearoomOne\Fields\DescriptionField' => 'classes/Fields/DescriptionField.php',
    'TearoomOne\Fields\SlugInfoField' => 'classes/Fields/SlugInfoField.php',
    'TearoomOne\Fields\ReviewField' => 'classes/Fields/ReviewField.php',
    'TearoomOne\Fields\PreviewField' => 'classes/Fields/PreviewField.php',
    'TearoomOne\LegacyPreviewSections' => 'classes/LegacyPreviewSections.php',
];

load($classes, __DIR__);

// Blueprints of Meta Kit 4 placed the preview in a `sections` list. Kirby 6
// only converts its own section types into fields, so the preview is mapped
// onto its field here. Kirby marks this map as unstable, hence the check.
// LegacyPreviewSections (hook below) gives such sections a name of their own.
if (property_exists(Normalizer::class, 'sectionFields') === true) {
    Normalizer::$sectionFields['seo-preview'] ??= 'seo-preview';
}


App::plugin(
    name: 'tearoom1/meta-kit',
    extends: [
        'options' => [
            'cache' => [
                'performer' => [
                    'active' => true
                ],
                'sitemap' => true
            ],
            'allowedRoles' => [], // Additional Kirby roles allowed to use the plugin (admins always allowed). Example: ['editor', 'client']
            // Grouped defaults are nested on purpose: Kirby's option lookup
            // prefers an exact flat key, so a flat default like 'ai.enabled'
            // would hide a site's nested 'ai' => ['enabled' => false].
            // Nested defaults let both notations in config.php win.
            'ai' => [
                'enabled' => true,
                'tone' => 'formal',
            ],
            'review' => [
                'enabled' => false,
            ],
            // api.* defaults live in ConfigHelper::getAiSettings(); defining
            // them here would override the AI settings made in the panel
            // validation defaults live in config/validation-defaults.json
            'excludeTemplates' => [],
            'excludeStatus' => [],
            'sitemap' => [
                'exclude' => ['error'],
            ],
            'autoGenerate' => false,
        ],
        // Panel texts; blueprints and components use the `meta-kit.*` keys
        'translations' => require __DIR__ . '/src/translations.php',
        'blueprints' => [
            'meta-kit/site' => __DIR__ . '/blueprints/site.yml',
            'meta-kit/page' => __DIR__ . '/blueprints/page.yml',
            // Flat field groups for SEO
            'fields/meta-group' => __DIR__ . '/blueprints/fields/meta-group.yml',
            'fields/seo-group' => __DIR__ . '/blueprints/fields/seo-group.yml',
            'fields/site-seo-group' => __DIR__ . '/blueprints/fields/site-seo-group.yml',
            'meta-kit/fields/og-image' => __DIR__ . '/blueprints/fields/og-image.php',
            // Blocks for other settings (AI provider, Sitemap, Robots)
            'blocks/mk-openrouter' => __DIR__ . '/blueprints/blocks/mk-openrouter.yml',
            'blocks/mk-sitemap' => __DIR__ . '/blueprints/blocks/mk-sitemap.yml',
            'blocks/mk-robots' => __DIR__ . '/blueprints/blocks/mk-robots.yml',
        ],
        'fields' => [
            'mk-title' => TitleField::class,
            'mk-description' => DescriptionField::class,
            'mk-slug-info' => SlugInfoField::class,
            'mk-review' => ReviewField::class,
            'seo-preview' => PreviewField::class,
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
                $languageCode = $languageCode ?? TearoomOne\MetaHelper::currentLanguageCode(kirby());
                if ($content === null) {
                    // Same content detection as the panel; pages with too
                    // little text return null instead of a guessed text
                    if (!TearoomOne\MetaKitController::hasEnoughContent($this)) {
                        return null;
                    }
                    $content = TearoomOne\MetaKitController::getContentForGeneration($this);
                }

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
                $languageCode = $languageCode ?? TearoomOne\MetaHelper::currentLanguageCode(kirby());
                if ($content === null) {
                    // Same content detection as the panel; pages with too
                    // little text return null instead of a guessed text
                    if (!TearoomOne\MetaKitController::hasEnoughContent($this)) {
                        return null;
                    }
                    $content = TearoomOne\MetaKitController::getContentForGeneration($this);
                }

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
                $languageCode = TearoomOne\MetaHelper::currentLanguageCode(kirby());
                return $metaKit->generateTitle($field->value(), ['language' => $languageCode]);
            },
            'toSeoDescription' => function ($field) {
                if (!TearoomOne\MetaKit::isAiEnabled()) {
                    return null;
                }

                $metaKit = new TearoomOne\MetaKit(kirby());
                $languageCode = TearoomOne\MetaHelper::currentLanguageCode(kirby());
                return $metaKit->generateDescription($field->value(), ['language' => $languageCode]);
            }
        ],
    ]);
