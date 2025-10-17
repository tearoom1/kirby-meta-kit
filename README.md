# Kirby Meta Stuff

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
composer require tearoom1/kirby-seo-ai
```

### Manual

1. Copy to `site/plugins/kirby-seo-ai`
2. Run `composer install` inside plugin directory
3. Get free API key from [OpenRouter.ai](https://openrouter.ai/)

## Configuration

Add to `site/config/config.php` (see `config.example.php` for all options):

```php
'tearoom1.meta-stuff' => [
    'api.key' => 'sk-or-v1-YOUR-KEY',  // Required
    'api.model' => 'mistralai/mistral-7b-instruct',  // Optional
    'maxDescriptionLength' => 160,
    'sitemap.enabled' => true,
    'sitemap.exclude' => ['error'],
    'schema.enabled' => true,
    'autoGenerate' => false,  // Auto-generate on save
]
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
    extends: seo-ai/site
```

**Page SEO:**
```yaml
# site/blueprints/pages/default.yml
tabs:
  seo:
    extends: seo-ai/page
```

### 3. Use Panel Features

**Live Preview:** See real-time Google/Twitter/Facebook appearance
**AI Generator:** Click "Generate with AI" button to create optimized descriptions
**Languages:** Auto-detects language (de, en, fr, es, it)

## What's Included

**Sitemap:** `/sitemap.xml` with styled view, exclusions, priorities
**Schema.org:** Organization, WebSite, WebPage, BreadcrumbList
**Meta Tags:** Title, description, keywords, canonical, noindex
**Social Media:** OG tags, Twitter Cards, optimized images (1200×630px)
**Multilanguage:** Hreflang tags, og:locale, per-language content

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

**Meta Descriptions:** <160 chars, unique per page, include keywords
**OG Images:** 1200×630px, PNG/JPG, avoid text-heavy images
**Auto-Generate:** Use panel button instead of auto-save for better control

## Requirements

- PHP 8.0+
- Kirby 4.0+ (Kirby 5 compatible)
- Composer
- OpenRouter API key (free tier available)

## License

MIT
