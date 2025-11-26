<?php

use TearoomOne\MetaKit;
use TearoomOne\MetaKitLicense;
use Kirby\Cms\App;
use Kirby\Plugin\Plugin;

if (!option('tearoom1.meta-kit.enabled', true)) {
    return;
}

@include_once __DIR__ . '/vendor/autoload.php';

$classes = [
    'TearoomOne\MetaKit' => 'classes/MetaKit.php',
    'TearoomOne\Sitemap' => 'classes/Sitemap.php',
    'TearoomOne\MetaHelper' => 'classes/MetaHelper.php',
    'TearoomOne\MetaKitController' => 'classes/MetaKitController.php',
    'TearoomOne\MetaKitLicense' => 'classes/MetaKitLicense.php',
];

// Only load LegacyMigration if enabled
if (option('tearoom1.meta-kit.legacyMigration', false)) {
    $classes['TearoomOne\LegacyMigration'] = 'classes/LegacyMigration.php';
}

load($classes, __DIR__);


App::plugin(
    name: 'tearoom1/meta-kit',
    license: fn(Plugin $plugin) => new MetaKitLicense($plugin,
        'Meta-Kit License',
        'meta-kit',
        'https://tearoom-kirby.ddev.site/de/kirby-plugins/meta-kit'),
    extends: [
        'options' => [
            'ai.enabled' => true,
            'ai.tone' => 'formal',
            'api.key' => null,
            'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'api.model' => '',
            'api.temperature' => 0.7,
            'maxDescriptionLength' => 160,
            'sitemap.include' => 'all',
            'sitemap.exclude' => ['error'],
            'autoGenerate' => false,
            'legacyMigration' => false,
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
            'system' => function () {
                $plugin = kirby()->plugin('tearoom1/meta-kit');
                return $plugin->license()->activationDialog();
            },
        ],
        'hooks' => require __DIR__ . '/src/hooks.php',
        'api' => [
            'routes' => require __DIR__ . '/src/api/routes.php',
        ],
        'routes' => require __DIR__ . '/src/routes/routes.php',
        'pageMethods' => [
            'generateSeoDescription' => function (?string $content = null, ?string $languageCode = null) {
                if (!TearoomOne\MetaKit::isAiEnabled()) {
                    return null;
                }

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
                if (!TearoomOne\MetaKit::isAiEnabled()) {
                    return null;
                }

                $metaKit = new MetaKit(kirby());
                $languageCode = kirby()->language()?->code() ?? 'en';
                return $metaKit->generateDescription($field->value(), ['language' => $languageCode]);
            }
        ],
    ]);
