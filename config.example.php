<?php

/**
 * Example configuration for Kirby Meta Kit Plugin
 *
 * Copy this to your site/config/config.php and customize as needed.
 */

return [
    'tearoom1.meta-kit' => [

        // ============================================
        // AI Integration Settings
        // ============================================

        // Enable/disable AI features (will hide AI buttons in panel)
        // AI is automatically disabled if api.key or api.model is empty
        'ai.enabled' => true,

        // Tone for AI-generated content ('formal' or 'informal')
        // formal = Sie in German, vous in French, etc.
        // informal = du in German, tu in French, etc.
        'ai.tone' => 'formal',

        // ============================================
        // API Settings (Required for AI features)
        // ============================================

        // AI provider: 'openrouter' (default), 'mistral' (EU) or 'custom'
        // (any OpenAI-compatible API, requires 'api.endpoint')
        'api.provider' => 'openrouter',

        // API key of the provider (OpenRouter: get one at https://openrouter.ai/)
        // Leave empty to disable AI features
        'api.key' => env('OPENROUTER_API_KEY', 'your-api-key-here'),

        // AI model to use for description generation
        // OpenRouter: any model from https://openrouter.ai/models
        // Mistral: e.g. 'mistral-small-latest', 'mistral-large-latest'
        // Leave empty to disable AI features
        'api.model' => 'google/gemma-4-31b-it:free',

        // API endpoint: only needed for 'custom'. When set, it overrides
        // the preset endpoint of 'openrouter' and 'mistral'.
        // 'api.endpoint' => 'https://llm.example.com/v1/chat/completions',

        // Temperature for AI generation (0.1 = focused, 1.0 = creative)
        'api.temperature' => 0.7,

        // Reasoning effort for reasoning models (GPT-5/6, Gemini 3, …):
        // 'none', 'minimal', 'low', 'medium' or 'high'. Lower = faster and cheaper.
        // null = model default. Ignored by models without reasoning and by
        // Mistral; on hybrid models (e.g. Claude) any value switches reasoning on.
        'api.reasoning' => null,

        // ============================================
        // SEO Settings
        // ============================================

        // Auto-generate descriptions when pages are saved
        // Warning: This makes API calls on every page save
        'autoGenerate' => false,

        // Minimum amount of page text (title excluded) needed for AI generation.
        // Pages with less text are skipped instead of letting the model guess.
        'ai.minContentLength' => 50,

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
        // Feature Toggles
        // ============================================

        // Enable/disable meta tags (title, description, canonical, etc.)
        'meta.enabled' => true,

        // Enable/disable OpenGraph & Twitter Card tags
        'opengraph.enabled' => true,

        // Enable/disable Schema.org JSON-LD structured data
        'schema.enabled' => true,

    ]
];
