# Kirby Meta Kit

AI-powered SEO plugin for Kirby 4 with automatic meta descriptions, live previews, sitemap generation, and Schema.org support.

[![Screenshot](screenshot.jpg)](https://github.com/tearoom1/kirby-content-watch)

## Features

- 🎛️ Dedicated Panel area for metadata management
- 🤖 AI-powered meta titles & descriptions via OpenRouter
- 👁️ Live panel previews (Google, Twitter, Facebook)
- ⚡ Bulk operations & legacy field migration
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
    
    // AI Prompts (optional - customize AI generation behavior)
    'prompt.title' => "Write a compelling meta title (30-65 characters) in {language} for the following content:\n\n{content}\n\nFocus on the main topic and include relevant keywords. Make it compelling and clickable for search results. The title MUST be between 30 and 65 characters long. Write ONLY the title, nothing else.\n\nTitle:",
    'prompt.description' => "Write a concise, engaging meta description (max 160 characters) in {language} for the following content:\n\n{content}\n\nFocus on the main topic and include relevant keywords. Make it compelling for search results. Write ONLY the description, nothing else.\n\nDescription:",

    // Feature Toggles
    'sitemap.enabled' => true,                 // Enable/disable sitemap generation
    'sitemap.exclude' => ['error', 'drafts'],  // Page IDs to exclude from sitemap
    'schema.enabled' => true,                  // Enable/disable Schema.org JSON-LD
    'autoGenerate' => false,                   // Auto-generate descriptions on page save
]
```

**Key Options Explained:**

- **`api.temperature`** (0.1-1.0): Controls AI creativity. Lower values (0.3) = consistent, factual. Higher values (0.9) = varied, creative. Default 0.7 is balanced.
- **`prompt.title`** / **`prompt.description`**: Customize the AI prompts. Use `{language}` for the language name and `{content}` for page content. Adjust tone, style, or specific requirements.
- **`autoGenerate`**: When `true`, automatically generates descriptions when saving pages. Recommended: `false` (use panel button instead for control).
- **`sitemap.exclude`**: Array of page IDs or regex patterns. Pages matching these won't appear in sitemap.

**Example Custom Prompt:**

```php
'prompt.description' => "Create a professional meta description (max 160 chars) in {language} for:\n\n{content}\n\nUse a formal tone. Focus on benefits and value. Include a call-to-action. Write ONLY the description:\n\n",
```

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

**API & Sitemap Settings:** Plugin defaults → Site panel → `config.php` (highest priority)

**Page Metadata:** Site defaults → Page fields (highest priority)

Examples:
- AI model: Set in panel, but `config.php` overrides it
- Meta description: Page field overrides site default
- Sitemap exclusions: Visual panel selector + `config.php` regex patterns work together

## Quick Start

### 1. Add Snippet to Template

```php
<head>
    <?php snippet('meta-kit/seo') ?>
</head>
```

This single snippet includes:
- Meta tags (title, description, canonical, keywords)
- OpenGraph & Twitter Cards
- Schema.org JSON-LD structured data

**Control what's included:**
```php
// In site/config/config.php
'tearoom1.meta-kit' => [
    'meta.enabled' => true,       // Meta tags
    'opengraph.enabled' => true,  // OG & Twitter
    'schema.enabled' => true,     // Schema.org
]
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

#### Meta Kit Area

The plugin adds a **Meta Kit** section to the Kirby Panel main menu with powerful metadata management tools:

**Features:**
- **Overview Dashboard:** Statistics showing metadata coverage across all pages
- **Bulk Operations:** Generate or update metadata for multiple pages at once
- **Legacy Migration:** Detect and convert old SEO fields to the new structure
- **Single Page Editor:** Quick edit dialog for meta title, description, and OG image
- **AI Generation:** Generate optimized meta titles (30-65 chars) and descriptions (max 160 chars)

**Access:** Click **"Meta Kit"** (wand icon) in the Panel sidebar

**Page Editor:**
* **Live Preview:** See real-time Google/Twitter/Facebook appearance
* **AI Generator:** Click "Generate with AI" button for optimized content
* **Manual Override:** Edit metadata directly in each page's SEO tab
* **Languages:** Auto-detects language (de, en, fr, es, it)

## What's Included

* **Panel Area:** Dedicated metadata management interface with dashboard and bulk operations
* **Unified Snippet:** Single `meta-kit/seo` snippet for all SEO needs
* **Meta Tags:** Title, description, keywords, canonical, noindex, hreflang
* **Social Media:** OpenGraph & Twitter Cards with optimized images (1200×630px)
* **Schema.org:** Organization, WebSite, WebPage, BreadcrumbList (JSON-LD)
* **Sitemap:** `/sitemap.xml` with styled view, exclusions, priorities
* **AI Generation:** Smart meta titles (30-65 chars) & descriptions (max 160 chars)
* **Multilanguage:** Full support with hreflang tags and og:locale
* **Configurable:** Toggle meta/opengraph/schema individually

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
