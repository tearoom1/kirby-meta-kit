# Kirby Meta Kit

AI-powered SEO plugin for Kirby 4 with automatic meta descriptions, live previews, sitemap generation, and Schema.org support.

[![Screenshot](screenshot.jpg)](https://github.com/tearoom1/kirby-content-watch)

## Features

- 🎛️ Dedicated Panel area for metadata management
- 🤖 AI-powered meta titles & descriptions via OpenRouter
- 👁️ Live panel previews (Google, Twitter, Facebook)
- ⚡ Bulk operations & legacy field migration
- 🗺️ Styled XML sitemap with multilanguage support
- 🤖 Dynamic robots.txt with user agent rules
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
    // AI Integration
    'ai.enabled' => true,                      // Enable/disable AI features (auto-disabled if no key/model)

    // AI Generation (Required for AI features)
    'api.key' => 'sk-or-v1-YOUR-KEY',         // Your OpenRouter API key (leave empty to disable AI)
    'api.model' => 'meta-llama/llama-3.2-3b-instruct:free',  // AI model (leave empty to disable AI)
    'api.temperature' => 0.7,                  // Creativity: 0.1 (focused) to 1.0 (creative)
    'ai.tone' => 'formal',                     // Language tone: 'formal' (Sie/vous) or 'informal' (du/tu)

    // SEO Settings
    'maxDescriptionLength' => 160,             // Max meta description length

    // AI Prompts (optional - customize AI generation behavior)
    'ai.prompt.title' => "Write a clear, direct meta title (30-65 characters) in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be specific and factual. Focus on what the page is actually about. {tone} Write ONLY the title, nothing else.\n\nTitle:",
    'ai.prompt.description' => "Write a clear, informative meta description (max 160 characters) in {language} for the following content:\n\n{content}\n\nAvoid marketing clichés like 'Discover', 'Unlock', 'Explore'. Be direct and specific. Describe what the page actually contains. {tone} Write ONLY the description, nothing else.\n\nDescription:",

    // Feature Toggles
    'sitemap.enabled' => true,                 // Enable/disable sitemap generation
    'sitemap.exclude' => ['error', 'drafts'],  // Page IDs to exclude from sitemap
    'schema.enabled' => true,                  // Enable/disable Schema.org JSON-LD
    'autoGenerate' => false,                   // Auto-generate descriptions on page save
]
```

**Key Options Explained:**

- **`ai.enabled`**: Explicitly enable/disable AI features. When `false`, AI buttons are hidden from the panel and all AI-related routes are disabled.
- **Auto-Disable**: AI features are automatically disabled if both `api.key` and `api.model` are empty, even if `ai.enabled` is `true`.
- **`api.temperature`** (0.1-1.0): Controls AI creativity. Lower values (0.3) = consistent, factual. Higher values (0.9) = varied, creative. Default 0.7 is balanced.
- **`ai.tone`**: Controls language formality in AI-generated content. Options:
  - `'formal'` (default): Uses formal address forms (Sie in German, vous in French, usted in Spanish)
  - `'informal'`: Uses informal address forms (du in German, tu in French, tú in Spanish)
- **`ai.prompt.title`** / **`ai.prompt.description`**: Customize the AI prompts. Available placeholders:
  - `{language}`: Language name (e.g., "German", "English")
  - `{content}`: Page content for context
  - `{tone}`: Automatically replaced with tone instruction based on `ai.tone` setting
- **`autoGenerate`**: When `true`, automatically generates descriptions when saving pages. Recommended: `false` (use panel button instead for control).
- **`sitemap.exclude`**: Array of page IDs or regex patterns. Pages matching these won't appear in sitemap.

**Disabling AI Features:**

To completely disable AI integration:

```php
'tearoom1.meta-kit' => [
    'ai.enabled' => false,  // Explicitly disable
]
```

Or simply leave API credentials empty:

```php
'tearoom1.meta-kit' => [
    'api.key' => '',     // Empty key auto-disables AI
    'api.model' => '',   // Empty model auto-disables AI
]
```

When AI is disabled:
- AI generation buttons are hidden in the panel
- `meta-kit-generator` field shows an info message instead of the generate button
- AI-related API routes are not registered
- Page methods (`generateSeoDescription()`) return `null`
- The plugin still provides manual SEO field management and sitemap generation

**Example Custom Prompt:**

```php
'ai.prompt.description' => "Create a professional meta description (max 160 chars) in {language} for:\n\n{content}\n\nUse a formal tone. Focus on benefits and value. Include a call-to-action. Write ONLY the description:\n\n",
```

### Validation ranges (Panel table)

The Meta Kit table validates the character lengths for title/description fields. You can configure the ranges globally and override them per template.

The template key must match `page.template` as shown in the Meta Kit table (e.g. `default`, `home`, `news-article`, ...).

**Example: base page like `/products` (template: `default`)**

```php
'tearoom1.meta-kit' => [
  'validation' => [
    'ranges' => [
      'title' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
      'ogTitle' => ['optimal' => ['min' => 20, 'max' => 60], 'warning' => ['min' => 15, 'max' => 75]],
      'description' => ['optimal' => ['min' => 140, 'max' => 160], 'warning' => ['min' => 126, 'max' => 176]],
      'ogDescription' => ['optimal' => ['min' => 150, 'max' => 250], 'warning' => ['min' => 135, 'max' => 300]],
    ],
    'templates' => [
      'default' => [
        'ranges' => [
          'title' => ['optimal' => ['min' => 40, 'max' => 60], 'warning' => ['min' => 30, 'max' => 75]],
        ]
      ],
    ]
  ],
]
```

**Example: news article (template: `article`)**

```php
'tearoom1.meta-kit' => [
  'validation' => [
    'templates' => [
      'article' => [
        'ranges' => [
          'title' => ['optimal' => ['min' => 40, 'max' => 65], 'warning' => ['min' => 30, 'max' => 75]],
        ]
      ],
    ]
  ],
]
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
- **Overview Dashboard:** Statistics showing metadata coverage across all pages (including site)
- **Bulk Operations:** Edit multiple pages at once with a table-based interface
  - Column-based layout for efficient editing
  - Edit meta title and description inline
  - AI generation buttons for each field
  - Character counters with validation (green/orange)
  - Apply changes with a single click
- **Legacy Migration:** Detect and convert old SEO fields to the new structure
- **Quick Edit:** Single-page metadata editor accessible from the main table
- **AI Generation:** Generate optimized meta titles and descriptions
  - **Smart Title Length:** Automatically adjusts title length (50-60 chars total) accounting for site name appending
  - **Description Length:** Max 160 characters optimized for search engines

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
* **Robots.txt:** `/robots.txt` with user agent rules, bad bot blocking, and Panel configuration
* **AI Generation:** Smart meta titles (auto-adjusted for site name) & descriptions (max 160 chars)
* **Multilanguage:** Full support with hreflang tags and og:locale
* **Configurable:** Toggle meta/opengraph/schema individually

## Programmatic Usage

```php
// Generate meta title (auto-adjusts for site name)
$title = $page->generateSeoTitle();
$title = $page->generateSeoTitle($content, 'de');
$title = $page->text()->toSeoTitle();

// Generate meta description
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

* **Meta Titles:**
  - Target 50-60 characters total (including site name)
  - AI generation automatically accounts for site name length
  - Place primary keywords near the beginning
  - Make titles compelling and clickable
  - Avoid ALL CAPS unless brand requires it
* **Meta Descriptions:** <160 chars, unique per page, include keywords
* **OG Images:** 1200×630px, PNG/JPG, avoid text-heavy images
* **Auto-Generate:** Use panel button instead of auto-save for better control

## Robots.txt Management

Meta Kit provides a powerful, fully configurable robots.txt generator that's managed through the Kirby Panel or config file.

### Features

- **Panel Interface**: Configure robots.txt rules visually in Site settings
- **User Agent Rules**: Create specific rules for different crawlers (Googlebot, Bingbot, etc.)
- **Bad Bot Blocking**: Automatically block known scrapers and spam bots
- **Sitemap Integration**: Automatically includes sitemap reference
- **Config Override**: Override or extend panel settings via config.php
- **Custom Directives**: Add advanced directives like Host, Crawl-delay, etc.

### Access Robots.txt

Your robots.txt file is automatically available at:
```
https://yoursite.com/robots.txt
```

### Panel Configuration

1. Go to **Site → Robots.txt** in the Panel
2. Enable "Custom Robots.txt"
3. Add user agent rules as needed
4. Configure options:
   - Include default rules (Allow: /)
   - Include sitemap reference
   - Block known bad bots

### Adding Custom Rules

**In the Panel:**

1. Click "Add" under User Agent Rules
2. Select a user agent (or choose "Custom")
3. Add allowed/disallowed paths
4. Set optional crawl delay
5. Save

**Example Panel Configuration:**

- **User Agent**: Googlebot
- **Allowed Paths**: /images/, /assets/
- **Disallowed Paths**: /panel/, /api/
- **Crawl Delay**: 0 (no delay)

### Config File Configuration

Override or extend panel settings in `site/config/config.php`:

```php
'tearoom1.meta-kit' => [
    'robots' => [
        // Enable/disable custom robots.txt
        'enabled' => true,

        // Include default "User-agent: * / Allow: /" rules
        'defaultRules' => true,

        // Include sitemap reference
        'includeSitemap' => true,

        // Block known bad bots
        'blockBadBots' => true,

        // Custom rules (extends panel rules)
        'rules' => [
            [
                'userAgent' => 'Googlebot',
                'allow' => ['/images/', '/assets/'],
                'disallow' => ['/panel/', '/api/'],
                'crawlDelay' => 0,
            ],
            [
                'userAgent' => 'AhrefsBot',
                'disallow' => ['/'], // Block completely
            ],
        ],

        // Custom directives (advanced)
        'customDirectives' => 'Host: www.example.com',
    ],
]
```

### Common Use Cases

**1. Block Specific Directories**

```php
'rules' => [
    [
        'userAgent' => '*',
        'disallow' => ['/panel/', '/api/', '/admin/'],
    ],
]
```

**2. Allow Only Search Engines**

```php
'rules' => [
    [
        'userAgent' => 'Googlebot',
        'allow' => ['/'],
    ],
    [
        'userAgent' => 'Bingbot',
        'allow' => ['/'],
    ],
    [
        'userAgent' => '*',
        'disallow' => ['/'], // Block all others
    ],
]
```

**3. Rate Limiting for Aggressive Crawlers**

```php
'rules' => [
    [
        'userAgent' => 'Bingbot',
        'allow' => ['/'],
        'crawlDelay' => 10, // 10 seconds between requests
    ],
]
```

**4. Block Bad Bots**

Enable in Panel or config:

```php
'robots' => [
    'blockBadBots' => true, // Blocks AhrefsBot, SemrushBot, MJ12bot, etc.
]
```

### Blocked Bad Bots

When "Block Bad Bots" is enabled, these user agents are automatically blocked:

- AhrefsBot
- SemrushBot
- MJ12bot
- DotBot
- BLEXBot
- PetalBot
- DataForSeoBot
- Pinterestbot/1.0
- MegaIndex.ru
- SeekportBot
- serpstatbot
- AspiegelBot

### Settings Priority

Settings merge in this order (lowest to highest priority):

1. **Plugin Defaults** - Basic allow all rules
2. **Panel Settings** - Configured in Site → Robots.txt
3. **Config File** - `tearoom1.meta-kit.robots` settings

Rules from config **extend** (not replace) panel rules.

### Generated Robots.txt Example

```
# Robots.txt for https://example.com
# Generated by Meta Kit for Kirby

User-agent: Googlebot
Allow: /images/
Allow: /assets/
Disallow: /panel/
Disallow: /api/

User-agent: Bingbot
Allow: /
Crawl-delay: 5

# Block known scrapers and spam bots
User-agent: AhrefsBot
Disallow: /

User-agent: SemrushBot
Disallow: /

User-agent: *
Allow: /

Sitemap: https://example.com/sitemap.xml
```

### Testing Your Robots.txt

1. Visit `https://yoursite.com/robots.txt` to see generated content
2. Use [Google's Robots Testing Tool](https://www.google.com/webmasters/tools/robots-testing-tool)
3. Verify syntax with [robots.txt validators](https://www.google.com/webmasters/tools/home)

### Troubleshooting

**Robots.txt not showing:**
- Check route is registered in routes.php (line 19-21)
- Clear Kirby cache
- Verify no .htaccess rules block /robots.txt

**Rules not applying:**
- Check Panel settings are saved
- Verify config syntax is correct
- Review generated output at /robots.txt
- Ensure "Enable Custom Robots.txt" toggle is ON

**Bad bots still crawling:**
- Some bots ignore robots.txt (add server-level blocks)
- Check server logs for actual user agent names
- Use .htaccess or firewall rules for persistent offenders

---

## Legacy Migration (old SEO fields → Meta Kit)

- Runs across all languages.
- Cleans up old legacy fields.

Enable migration routes in your config:

```php
'tearoom1.meta-kit' => [
  'legacyMigration' => true,
]
```

Run it from the Panel:

- Open the Meta Kit area
- Click “Legacy Migration” next to the language switcher
- Review the summary and click “Migrate All Languages”

> Warning: This will remove the old SEO fields from your pages. Make sure to backup your data before running this.


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
