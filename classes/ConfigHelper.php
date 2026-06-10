<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Content\Field;

/**
 * Centralized configuration and field reading utilities
 */
class ConfigHelper
{
    private static $siteSettingsCache = null;
    /**
     * Get string value from field with fallback
     */
    public static function getString(?Field $field, string $default = ''): string
    {
        if ($field && $field->isNotEmpty()) {
            return $field->value();
        }
        return $default;
    }

    /**
     * Get boolean value from field with fallback
     */
    public static function getBool(?Field $field, bool $default = false): bool
    {
        if ($field && $field->isNotEmpty()) {
            return $field->toBool();
        }
        return $default;
    }

    /**
     * Get float value from field with fallback
     */
    public static function getFloat(?Field $field, float $default = 0.0): float
    {
        if ($field && $field->isNotEmpty()) {
            return $field->toFloat();
        }
        return $default;
    }

    /**
     * Merge configuration with priority: defaults < site settings < config.php
     */
    public static function mergeOptions(array $defaults, array $siteSettings): array
    {
        $configSettings = kirby()->option('tearoom1.meta-kit', []);
        return array_merge($defaults, $siteSettings, $configSettings);
    }

    /**
     * Get site SEO settings (cached)
     */
    public static function getSiteSettings(): array
    {
        if (self::$siteSettingsCache !== null) {
            return self::$siteSettingsCache;
        }

        $site = kirby()->site();

        self::$siteSettingsCache = [
            'appendSiteName' => self::getBool($site->appendSiteName(), true),
            'appendSiteNameTo' => self::getString($site->appendSiteNameTo(), 'meta,og'),
            'siteMetaTitle' => self::getString($site->metaTitle()) ?: $site->title()->value(),
            'siteMetaDescription' => self::getString($site->metaDescription()),
            'siteHasOgImage' => $site->ogImage()->isNotEmpty(),
            'titleSeparator' => self::getString($site->titleSeparator(), '|'),
        ];

        return self::$siteSettingsCache;
    }

    /**
     * Clear cached site settings (call after site updates)
     */
    public static function clearCache(): void
    {
        self::$siteSettingsCache = null;
    }

    /**
     * Get validation ranges for a field type, with template-specific override support
     */
    public static function getValidationRanges(string $fieldType, ?string $template = null): array
    {
        $validation = option('tearoom1.meta-kit.validation', []);
        $ranges = $validation['ranges'] ?? [];
        $templates = $validation['templates'] ?? [];

        // Map field types to config keys
        $fieldKey = match ($fieldType) {
            'title', 'metaTitle' => 'title',
            'ogTitle' => 'ogTitle',
            'description', 'metaDescription' => 'description',
            'ogDescription' => 'ogDescription',
            default => 'title'
        };

        // Check for template-specific ranges
        if ($template && isset($templates[$template][$fieldKey])) {
            return $templates[$template][$fieldKey];
        }

        // Default ranges by field type
        $defaults = [
            'title' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
            'ogTitle' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
            'description' => ['optimal' => ['min' => 140, 'max' => 160], 'warning' => ['min' => 126, 'max' => 176]],
            'ogDescription' => ['optimal' => ['min' => 140, 'max' => 160], 'warning' => ['min' => 126, 'max' => 176]],
        ];

        return $ranges[$fieldKey] ?? $defaults[$fieldKey] ?? $defaults['title'];
    }

    /**
     * Get OpenRouter settings from site panel or config
     */
    public static function getOpenRouterSettings(): array
    {
        $defaults = [
            'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'api.model' => 'google/gemma-4-31b-it:free',
            'api.temperature' => 0.7,
            'ai.tone' => 'formal',
            'maxDescriptionLength' => 160,
            'ai.prompt.title' => "Write a clear, direct meta title {optimal_length} in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be specific and factual. Focus on what the page is actually about. {tone} Write ONLY the title, nothing else.\n\nTitle:",
            'ai.prompt.description' => "Write a clear, informative meta description {optimal_length} in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be direct and specific. Describe what the page actually contains. {tone} Write ONLY the description, nothing else.\n\nDescription:",
        ];

        $siteSettings = [];
        $openrouter = MetaHelper::getSeoData(kirby()->site()->metaKitOpenrouter());
        if ($openrouter) {
            if ($openrouter->apiKey()->isNotEmpty()) {
                $siteSettings['api.key'] = $openrouter->apiKey()->value();
            }
            if ($openrouter->model()->isNotEmpty()) {
                $siteSettings['api.model'] = $openrouter->model()->value();
            }
            if ($openrouter->temperature()->isNotEmpty()) {
                $siteSettings['api.temperature'] = $openrouter->temperature()->toFloat();
            }
        }

        return self::mergeOptions($defaults, $siteSettings);
    }

    /**
     * Get sitemap settings from site panel or config
     */
    public static function getSitemapSettings(): array
    {
        $defaults = [
            'sitemap.exclude' => ['error'],
            'sitemap.includeUnlisted' => false,
            'sitemap.changefreq.default' => 'monthly',
            'sitemap.changefreq.templates' => [
                'home' => 'daily',
                'news' => 'weekly',
                'article' => 'weekly',
                'blog' => 'weekly',
                'post' => 'weekly',
                'imprint' => 'yearly',
                'privacy' => 'yearly',
                'terms' => 'yearly',
                'legal' => 'yearly',
            ],
            'sitemap.changefreq.slugs' => [
                'imprint' => 'yearly',
                'privacy' => 'yearly',
                'datenschutz' => 'yearly',
                'impressum' => 'yearly',
                'terms' => 'yearly',
                'agb' => 'yearly',
                'contact' => 'monthly',
                'kontakt' => 'monthly',
            ],
            'sitemap.priority.templates' => [
                'home' => 1.0,
                'news' => 0.9,
                'article' => 0.8,
                'blog' => 0.9,
                'post' => 0.8,
                'imprint' => 0.3,
                'privacy' => 0.3,
                'terms' => 0.3,
                'legal' => 0.3,
            ],
            'sitemap.priority.slugs' => [
                'impressum' => 0.3,
                'datenschutz' => 0.3,
                'privacy' => 0.3,
                'imprint' => 0.3,
                'terms' => 0.3,
                'agb' => 0.3,
            ],
        ];

        $siteSettings = [];
        $siteSitemap = MetaHelper::getSeoData(kirby()->site()->metaKitSitemap());
        if ($siteSitemap) {
            if ($siteSitemap->exclude()->isNotEmpty()) {
                $siteSettings['sitemap.exclude.pages'] = $siteSitemap->exclude()->toPages();
            }
            if ($siteSitemap->includeUnlisted()->isNotEmpty()) {
                $siteSettings['sitemap.includeUnlisted'] = $siteSitemap->includeUnlisted()->toBool();
            }
            if ($siteSitemap->changefreqDefault()->isNotEmpty()) {
                $siteSettings['sitemap.changefreq.default'] = $siteSitemap->changefreqDefault()->value();
            }
            if ($siteSitemap->priorityHome()->isNotEmpty()) {
                $siteSettings['sitemap.priorityHome'] = $siteSitemap->priorityHome()->toFloat();
            }
            if ($siteSitemap->priorityDefault()->isNotEmpty()) {
                $siteSettings['sitemap.priorityDefault'] = $siteSitemap->priorityDefault()->toFloat();
            }
        }

        return self::mergeOptions($defaults, $siteSettings);
    }
}
