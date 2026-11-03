# Configuration

[← Back to the README](../README.md)

Meta Kit is configured in two layers: technical settings in `site/config/config.php`, content settings in the Panel. This page covers both, how they combine, and who may open the Meta Kit area.


Meta Kit uses a two-layer configuration system for maximum flexibility:

## Layer 1: Config File (Technical Settings)

**Location**: `site/config/config.php`

This is where developers set technical defaults, validation rules, and AI integration.

```php
'tearoom1.meta-kit' => [
    // ====================================
    // ACCESS CONTROL
    // ====================================

    'allowedRoles' => [],  // Additional non-admin roles allowed to use Meta Kit. See Access Control below.

    // ====================================
    // AI INTEGRATION
    // ====================================

    'ai.enabled' => true,       // Master toggle for AI generation
    'review.enabled' => false,  // Opt-in: show experimental AI content review in the Panel

    // AI Provider Configuration
    'api.provider' => 'openrouter',  // 'openrouter' (default), 'mistral' or 'custom' (see Choosing a Provider)
    'api.key' => 'sk-or-v1-YOUR-KEY',  // API key of the provider; get a free key at openrouter.ai
    'api.model' => 'google/gemma-4-31b-it:free',  // Models: see ai.md
    'api.temperature' => 0.7,  // 0.1 (focused) to 1.0 (creative)
    'api.reasoning' => null,  // Reasoning effort: 'none', 'minimal', 'low', 'medium', 'high' (null = model default)

    // AI Behavior
    'ai.tone' => 'formal',  // 'formal' (Sie/vous) or 'informal' (du/tu)

    // ====================================
    // VALIDATION RULES
    // ====================================

    'validation' => [], // see validation.md

    // ====================================
    // FEATURES
    // ====================================

    'sitemap.enabled' => true,
    'sitemap.exclude' => ['error', 'drafts'],  // Page IDs or patterns
    'languages.available' => null,  // Callback: does a page exist in a language? See sitemap-robots.md
    'schema.enabled' => true,
    'autoGenerate' => false,  // Generate a missing meta description after saving a page
    'ai.minContentLength' => 50,  // Skip AI generation for pages with less text (title excluded)
    'excludeTemplates' => [],  // Hide from panel table
    'excludeStatus' => [],  // Hide draft/unlisted pages

    // Robots.txt configuration
    'robots' => [
        'enabled' => true,
        'blockBadBots' => true,  // Block AhrefsBot, SemrushBot, etc.
        'blockAiCrawlers' => false,  // Block AI training crawlers (GPTBot, ClaudeBot, …)
        'defaultRules' => true,
        'includeSitemap' => true,
    ],
    'llms.enabled' => false,  // Publish /llms.txt (overrides the panel toggle)
    'redirects.enabled' => true,  // 301 redirects from old URLs after slug changes and moves

];
```

## Layer 2: Panel Settings (Content Settings)

**Location**: Site → SEO & Social Media in Kirby Panel

This is where editors configure site-wide content defaults and behavior:

**SEO Tab:**
- Default meta title and description
- Title separator (`|`, `-`, `•`, etc.)
- Auto-append site name toggle
- Choose which field types get site name (meta only, OG only, or both)
- Default robots directive

**AI Settings Tab:**
- Provider (OpenRouter, Mistral or another OpenAI-compatible API), API key and model selection
- Reasoning effort for reasoning models
- Creativity level (temperature slider)
- Can override config.php settings if needed

**Social Media Tab:**
- Social profile URLs (Facebook, Instagram, LinkedIn, YouTube, Vimeo, SoundCloud, Spotify, Bandcamp, Mastodon, Bluesky and more)
- Used in Schema.org `sameAs` property: every URL counts, the platform only labels the row in the Panel

**Sitemap Tab:**
- Visual page selector for exclusions
- Homepage priority (0.1-1.0)
- Default page priority

**Robots.txt Tab:**
- Enable/disable custom robots.txt
- User agent rules (per-bot configuration)
- Allowed and disallowed paths
- Crawl delay settings

### Multilingual Sites

Settings are maintained once, in the default language, and apply to every language. Words stay translatable.

| Shared by all languages | Translated per language |
|---|---|
| Site name appending, where it goes, title separator | Default meta title (the site name) |
| Default robots directive | Default meta description |
| Schema.org type, name, email and job title | Default author |
| Social profiles | Default share image |
| AI, Sitemap and Robots.txt settings, redirects | |

In any other language the Panel shows the shared settings greyed out with the value of the default language. Meta Kit always reads them from the published default language, even when an older version left a copy in a translation file. The next save of the site removes such copies from all translations, in published content and in unsaved changes.

While only a translation has unsaved changes, the Panel of that language shows the shared settings empty or switched off, and hides the fields that depend on them (separator, where the site name goes). This is how Kirby builds unsaved changes for translations; the stored settings and the website are not affected, and after saving or discarding the Panel shows the settings again.

To keep a setting translatable, redefine the field in your site blueprint with `translate: true`. Meta Kit then reads and keeps it per language:

```yaml
tabs:
  seo:
    extends: meta-kit/site
    columns:
      social:
        fields:
          metaKitSocialSites:
            translate: true
```

## Settings Priority

Settings merge in this order (lowest to highest priority):

1. **Plugin Defaults** - Built-in fallback values
2. **Panel Settings** - Configured by editors
3. **Config File** - Developer overrides (highest priority)

**Examples:**
- AI model set in Panel can be overridden in config.php (options set to `null` in config.php don't override the Panel)
- Validation ranges in config.php apply unless template-specific rules exist
- Sitemap exclusions from Panel and config.php work together (combined)

## Access Control

By default, **only users with the `admin` role** can access Meta Kit — that includes the Meta Kit panel area, the menu entry, the bulk editor, and every Meta Kit API route (page listing, single-field apply, AI generation, and the experimental review). Non-admins will not see the menu item, and any direct API call returns `403 Forbidden`.

To grant access to additional Kirby roles, list them in `allowedRoles`:

```php
'tearoom1.meta-kit' => [
    'allowedRoles' => ['editor'],
]
```

Notes:
- Admins are **always** allowed; you do not need to include `'admin'` in the list.
- Users with any of the listed roles can read SEO data for every page (including drafts) and trigger AI generation/review, which consumes your AI provider quota. Only grant this to roles you trust.
- Saving generated values still goes through Kirby's normal page-update permissions, so a role allowed by `allowedRoles` cannot use Meta Kit to overwrite fields on pages they are not normally allowed to edit.
