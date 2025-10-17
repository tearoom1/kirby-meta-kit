# Kirby Meta Kit

AI-powered SEO plugin for Kirby 4 with automatic meta descriptions, live previews, sitemap generation, and Schema.org support.

## Features

- 🤖 AI-powered meta descriptions via OpenRouter
- 👁️ Live panel previews (Google, Twitter, Facebook)
- 🗺️ Styled XML sitemap with multilanguage support
- 🏗️ Schema.org JSON-LD structured data
- 📱 OpenGraph & Twitter Cards (1200×630px)
- 🌍 Full multilanguage support with hreflang
- 🎯 Configurable title format & separators
- ⚡ Kirby 4 & 5 compatible

## Installation

### Via Composer

```bash
composer require tearoom1/kirby-meta-kit
```

### Manual

1. Copy to `site/plugins/meta-kit`
2. Run `composer install` inside plugin directory
3. Get free API key from [OpenRouter.ai](https://openrouter.ai/)

## Configuration

The plugin uses two configuration layers that work together:

### 1. Config File (`site/config/config.php`)

Core plugin settings, API credentials, and feature toggles:

```php
'tearoom1.meta-kit' => [
    // AI Generation (Required for AI features)
    'api.key' => 'sk-or-v1-YOUR-KEY',         // Your OpenRouter API key
    'api.model' => 'meta-llama/llama-3.2-3b-instruct:free',  // AI model to use
    'api.temperature' => 0.7,                  // Creativity: 0.1 (focused) to 1.0 (creative)

    // SEO Settings
    'maxDescriptionLength' => 160,             // Max meta description length

    // Feature Toggles
    'sitemap.enabled' => true,                 // Enable/disable sitemap generation
    'sitemap.exclude' => ['error', 'drafts'],  // Page IDs to exclude from sitemap
    'schema.enabled' => true,                  // Enable/disable Schema.org JSON-LD
    'autoGenerate' => false,                   // Auto-generate descriptions on page save
]
```

**Key Options Explained:**

- **`api.temperature`** (0.1-1.0): Controls AI creativity. Lower values (0.3) = consistent, factual. Higher values (0.9) = varied, creative. Default 0.7 is balanced.
- **`autoGenerate`**: When `true`, automatically generates descriptions when saving pages. Recommended: `false` (use panel button instead for control).
- **`sitemap.exclude`**: Array of page IDs or regex patterns. Pages matching these won't appear in sitemap.

### 2. Site Settings (Panel)

Content-focused settings managed in **Site → SEO & Social Media**:

**SEO Tab:**
- Title separator (|, -, •, etc.)
- Auto-append site name toggle
- Default meta title, description, keywords
- Default social media image

**Social Media Tab:**
- Social profile URLs (Facebook, Twitter, LinkedIn, etc.)
- Added to Schema.org for `sameAs` property

**Sitemap Tab:**
- Visual page selector to exclude from sitemap
- Homepage priority (0.1-1.0)
- Default page priority (0.1-1.0)

### How Settings Merge

The plugin combines both layers:

1. **Config file** provides defaults and API credentials
2. **Site settings** override with content-specific values
3. **Page fields** override everything for that specific page

Example: Meta description priority
```
Page SEO field → Site default description → Auto-generated → Empty
```

## Quick Start

### 1. Add Snippets to Template

```php
<head>
    <?php snippet('seo/meta') ?>
    <?php snippet('seo/opengraph') ?>
    <?php snippet('seo/schema') ?>
</head>
```

### 2. Extend Blueprints

**Site settings:**
```yaml
# site/blueprints/site.yml
tabs:
  seo:
    extends: meta-kit/site
```

**Page SEO:**
```yaml
# site/blueprints/pages/default.yml
tabs:
  seo:
    extends: meta-kit/page
```

### 3. Use Panel Features

* **Live Preview:** See real-time Google/Twitter/Facebook appearance
* **AI Generator:** Click "Generate with AI" button to create optimized descriptions
* **Languages:** Auto-detects language (de, en, fr, es, it)

## What's Included

* **Sitemap:** `/sitemap.xml` with styled view, exclusions, priorities
* **Schema.org:** Organization, WebSite, WebPage, BreadcrumbList
* **Meta Tags:** Title, description, keywords, canonical, noindex
* **Social Media:** OG tags, Twitter Cards, optimized images (1200×630px)
* **Multilanguage:** Hreflang tags, og:locale, per-language content

## Programmatic Usage

```php
// Generate description
$desc = $page->generateSeoDescription();
$desc = $page->generateSeoDescription($content, 'de');
$desc = $page->text()->toSeoDescription();
```

## API Endpoint

```bash
POST /api/seo-ai/generate
{"text": "content", "language": "de"}
# Returns: {"status": "success", "description": "..."}
```

## Best Practices

* **Meta Descriptions:** <160 chars, unique per page, include keywords
* **OG Images:** 1200×630px, PNG/JPG, avoid text-heavy images
* **Auto-Generate:** Use panel button instead of auto-save for better control

## Requirements

- PHP 8.0+
- Kirby 4.0+ (Kirby 5 compatible)
- Composer
- OpenRouter API key (free tier available)

## License

This plugin is licensed under the [MIT License](LICENSE)

## Credits

- Developed by Mathis Koblin
- Assisted by AI Claude 4.5 Sonnet

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://coff.ee/tearoom1)
