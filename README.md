# Kirby SEO AI Plugin

AI-powered SEO and OpenGraph management for Kirby CMS with automatic meta description generation using OpenRouter and Mistral AI.

## Features

- 🤖 **AI-Powered Meta Descriptions** - Generate SEO-optimized descriptions using Mistral via OpenRouter
- 👁️ **Live Panel Preview** - Real-time preview of Google, Twitter, and Facebook appearance
- 🎯 **One-Click AI Generation** - Generate descriptions directly in the panel with a button
- 🏗️ **Schema.org JSON-LD** - Automatic structured data for Organization, WebSite, WebPage, and BreadcrumbList
- 🌍 **Multilanguage Support** - Full support for multilingual sites with hreflang tags
- 🗺️ **XML Sitemap** - Automatic sitemap generation with multilanguage support
- 📱 **Social Media Ready** - OpenGraph and Twitter Card meta tags with 1200×630px image previews
- 🎯 **SEO Optimization** - Meta titles with configurable separators, descriptions, keywords, canonical URLs
- 🎨 **Customizable Title Format** - Choose separator (|, -, –, —, •, /) and auto-append site name
- 🔧 **Panel Integration** - Blueprint fields for easy content management
- ⚡ **Kirby 5 Compatible** - Built for the latest Kirby version

## Installation

### Via Composer

```bash
composer require tearoom1/kirby-seo-ai
```

### Manual Installation

1. Download and extract the plugin
2. Copy to `site/plugins/kirby-seo-ai`
3. Run `composer install` inside the plugin directory

## Configuration

Add your OpenRouter API key to `site/config/config.php`:

> **Tip:** See `config.example.php` in the plugin directory for a complete configuration example with all available options.

```php
return [
    'tearoom1.meta-stuff' => [
        // Required: Your OpenRouter API key
        'api.key' => 'your-openrouter-api-key',
        
        // Optional: AI model to use (default: mistralai/mistral-7b-instruct)
        'api.model' => 'mistralai/mistral-7b-instruct',
        
        // Optional: API endpoint (default: OpenRouter)
        'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
        
        // Optional: Temperature for AI generation (0.1-1.0, default: 0.7)
        'api.temperature' => 0.7,
        
        // Optional: Max description length (default: 160)
        'maxDescriptionLength' => 160,
        
        // Optional: Enable/disable sitemap generation (default: true)
        'sitemap.enabled' => true,
        
        // Optional: Pages to exclude from sitemap (array of page IDs)
        'sitemap.exclude' => ['error'],
        
        // Optional: Enable/disable Schema.org JSON-LD (default: true)
        'schema.enabled' => true,
        
        // Optional: Auto-generate descriptions on page save (default: false)
        'autoGenerate' => false,
    ]
];
```

## Usage

### Template Integration

Add the SEO snippets to your template's `<head>` section:

```php
<!DOCTYPE html>
<html lang="<?= kirby()->language()?->code() ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php snippet('seo/meta') ?>
    <?php snippet('seo/opengraph') ?>
    <?php snippet('seo/schema') ?>
    
    <!-- Your other head elements -->
</head>
<body>
    <?= $page->text()->kirbytext() ?>
</body>
</html>
```

### Schema.org Structured Data

The `seo/schema` snippet automatically generates JSON-LD structured data for:
- **Organization** - Your site's organization info with logo and social profiles
- **WebSite** - Website schema with search action
- **WebPage** - Current page schema with name, description, and image
- **BreadcrumbList** - Breadcrumb navigation (automatically generated for child pages)

This helps search engines better understand your content and may enable rich results in search.

#### Enable/Disable Schema.org

You can disable Schema.org JSON-LD generation if not needed:

```php
'tearoom1.meta-stuff' => [
    'schema.enabled' => false,
]
```

The snippet will simply return without generating any markup if disabled.

### Panel Features

The plugin provides powerful panel features for managing SEO:

#### SEO Preview

The plugin automatically shows live previews of how your page will appear on:
- **Google Search** - See the title, URL, and description as they appear in search results
- **Twitter** - Preview the Twitter Card with image, title, and description
- **Facebook** - Preview the OpenGraph share with image, title, and description

The preview updates in real-time as you edit your content!

#### AI Description Generator

Generate meta descriptions with a single click:
1. Click the **"Generate with AI"** button
2. The plugin analyzes your page content
3. AI generates an optimized description in the current language
4. Description is automatically inserted into the meta description field

The AI respects your site's language and generates descriptions in German, English, French, Spanish, or Italian.

### Blueprint Integration

#### Site Blueprint

The plugin provides site-wide SEO settings accessible in **Site → SEO & Social Media**:

**SEO Tab:**
- Title Separator (|, -, etc.)
- Append Site Name toggle
- Default meta title, description, keywords
- Default social media image

**Social Media Tab:**
- Social profiles (Facebook, Twitter, Instagram, etc.)
- Used in Schema.org JSON-LD for `sameAs` property

**Sitemap Tab:**
- Exclude pages from sitemap (page selector)
- Homepage priority (0.1-1.0)
- Default page priority (0.1-1.0)

Extend your site blueprint with SEO fields:

```yaml
# site/blueprints/site.yml
tabs:
  content:
    # Your content fields
  
  seo:
    extends: seo-ai/site
```

#### Page Blueprint

Add SEO fields to your page blueprints:

```yaml
# site/blueprints/pages/default.yml
title: Default Page

tabs:
  content:
    # Your content fields
  
  seo:
    extends: seo-ai/page
```

Or include as a section:

```yaml
sections:
  content:
    # Your content sections
  
  seo:
    type: fields
    extends: seo-ai/page
```

### Programmatic Usage

#### Generate Description

```php
// In templates or controllers
$description = $page->generateSeoDescription();

// With custom content
$description = $page->generateSeoDescription($customContent);

// With specific language
$description = $page->generateSeoDescription(null, 'de');

// Field method
$description = $page->text()->toSeoDescription();
```

### Sitemap

The plugin automatically generates a styled sitemap at:

```
https://yourdomain.com/sitemap.xml
```

#### Enable/Disable Sitemap

You can disable sitemap generation if not needed:

```php
'tearoom1.meta-stuff' => [
    'sitemap.enabled' => false,
]
```

#### Styled Sitemap View

The sitemap includes an XSL stylesheet that provides a beautiful, human-readable view:
- Color-coded priority badges
- Statistics (total URLs, last update)
- Responsive table layout
- Clickable URLs

#### Multilanguage Sitemap

For multilanguage sites, the sitemap includes:
- All page URLs in the default language
- Alternate language links (xhtml:link tags)
- Proper hreflang annotations

## Multilanguage Support

The plugin fully supports Kirby's multilanguage feature:

### Language Detection

- Automatically detects the current language
- Generates descriptions in the correct language
- Adds hreflang tags for all language versions
- Includes og:locale for OpenGraph

### Supported Languages

The AI can generate descriptions in:
- German (de)
- English (en)
- French (fr)
- Spanish (es)
- Italian (it)

More languages can be added in the configuration.

## Available Fields

### Meta Fields

- **metaTitle** - Custom meta title (fallback: page title | site title)
- **metaDescription** - Custom meta description (with AI generation)
- **metaKeywords** - Keywords for the page
- **canonicalUrl** - Custom canonical URL
- **noIndex** - Hide page from search engines

### OpenGraph Fields

- **ogTitle** - Social media title (fallback: meta title)
- **ogDescription** - Social media description (fallback: meta description)
- **ogImage** - Social media image (1200×630px recommended)

## API Endpoint

The plugin provides a REST API endpoint for generating descriptions:

```bash
POST /api/seo-ai/generate
Authorization: Bearer <panel-token>
Content-Type: application/json

{
  "text": "Your content here",
  "language": "de"
}
```

Response:

```json
{
  "status": "success",
  "description": "Generated description here"
}
```

## Getting an OpenRouter API Key

1. Go to [OpenRouter.ai](https://openrouter.ai/)
2. Sign up for a free account
3. Navigate to API Keys section
4. Create a new API key
5. Add it to your Kirby config

**Note:** Mistral AI is free to use on OpenRouter, but you may need to add a small credit for API access.

## Performance Tips

1. **Content Length**: The plugin automatically limits content to 1000 characters before sending to AI
2. **Caching**: Consider implementing page caching for better performance
3. **Auto-Generate**: Only enable `autoGenerate` if you want descriptions created on every page save
4. **Manual Generation**: Use the panel button to generate descriptions on-demand

## Hooks

The plugin includes a hook to auto-generate descriptions when pages are saved (if enabled):

```php
// Enable in config
'tearoom1.meta-stuff' => [
    'autoGenerate' => true,
]
```

## Error Handling

- Missing API key: Throws exception
- API errors: Logged to Kirby log, returns null
- Empty content: Returns null without making API call
- Invalid responses: Sanitized and truncated to max length

## SEO Best Practices

### Meta Descriptions
- Keep under 160 characters
- Include target keywords naturally
- Make it compelling for click-throughs
- Unique for each page

### Social Media Images
- Use 1200×630px images
- PNG or JPG format
- Avoid text-heavy images
- Test on multiple platforms

### Hreflang Tags
- Automatically added for multilanguage sites
- Includes x-default for default language
- Helps search engines serve correct language

## License

MIT

## Credits

- Built for Kirby CMS
- Uses OpenRouter API for AI generation
- Powered by Mistral AI

## Support

For issues and feature requests, please use the GitHub repository issue tracker.

## Requirements

- PHP 8.0 or higher
- Kirby 4.0 or higher (Kirby 5 compatible)
- GuzzleHTTP 7.0 or higher
- OpenRouter API key
