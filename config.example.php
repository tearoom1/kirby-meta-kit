<?php

/**
 * Example configuration for Kirby Meta Kit Plugin
 *
 * Copy this to your site/config/config.php and customize as needed.
 */

return [
    'tearoom1.meta-kit' => [

        // ============================================
        // API Settings (Required for AI features)
        // ============================================

        // Your OpenRouter API key (get one at https://openrouter.ai/)
        'api.key' => env('OPENROUTER_API_KEY', 'your-api-key-here'),

        // AI model to use for description generation
        // Options: mistralai/mistral-7b-instruct, openai/gpt-3.5-turbo, etc.
        'api.model' => 'meta-llama/llama-3.2-3b-instruct:free',

        // API endpoint (usually no need to change)
        'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',

        // Temperature for AI generation (0.1 = focused, 1.0 = creative)
        'api.temperature' => 0.7,

        // ============================================
        // SEO Settings
        // ============================================

        // Maximum length for generated descriptions
        'maxDescriptionLength' => 160,

        // Auto-generate descriptions when pages are saved
        // Warning: This makes API calls on every page save
        'autoGenerate' => false,

        // ============================================
        // Sitemap Settings
        // ============================================

        // Enable/disable sitemap generation at /sitemap.xml
        'sitemap.enabled' => true,

        // Pages to exclude from sitemap (array of page IDs)
        'sitemap.exclude' => [
            'error',
            'search',
            'draft-page',
        ],

        // ============================================
        // Schema.org Settings
        // ============================================

        // Enable/disable Schema.org JSON-LD structured data
        'schema.enabled' => true,

    ]
];
