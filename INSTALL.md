# Installation Guide

## Quick Start

### 1. Install Dependencies

From the plugin directory, run:

```bash
cd site/plugins/kirby-seo-ai
composer install
```

### 2. Get OpenRouter API Key

1. Visit [OpenRouter.ai](https://openrouter.ai/)
2. Sign up for a free account
3. Go to Keys section and create a new API key
4. Copy your API key

### 3. Configure Plugin

Add to `site/config/config.php`:

```php
return [
    // ... your other config
    
    'tearoom1.seo-ai' => [
        'api.key' => 'sk-or-v1-YOUR-API-KEY-HERE',
    ]
];
```

### 4. Add to Templates

Add to your main template's `<head>` section (e.g., `site/templates/default.php`):

```php
<!DOCTYPE html>
<html lang="<?= kirby()->language()?->code() ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php snippet('seo/meta') ?>
    <?php snippet('seo/opengraph') ?>
    
    <!-- Your CSS and other head elements -->
</head>
<body>
    <?= $page->text()->kirbytext() ?>
</body>
</html>
```

### 5. Test

1. Visit your site frontend
2. View page source
3. Check for meta tags in `<head>` section
4. Visit `/sitemap.xml` to see your sitemap

## Advanced Configuration

### Full Options

```php
'tearoom1.seo-ai' => [
    // Required
    'api.key' => 'your-api-key',
    
    // Optional - AI Settings
    'api.model' => 'mistralai/mistral-7b-instruct',
    'api.endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
    'api.temperature' => 0.7,
    
    // Optional - SEO Settings
    'maxDescriptionLength' => 160,
    
    // Optional - Sitemap Settings
    'sitemap.exclude' => ['error', 'drafts'],
    
    // Optional - Auto-generate descriptions on save
    'autoGenerate' => false, // Set to true to auto-generate
]
```

### Blueprint Integration

#### Option 1: Extend Site Blueprint

```yaml
# site/blueprints/site.yml
tabs:
  content:
    # Your content
  
  seo:
    extends: seo-ai/site
```

#### Option 2: Extend Page Blueprint

```yaml
# site/blueprints/pages/default.yml
tabs:
  content:
    # Your content
  
  seo:
    extends: seo-ai/page
```

## Testing AI Generation

### In Templates

```php
// Generate description for current page
$description = $page->generateSeoDescription();

// Generate for specific content
$customText = "Your content here...";
$description = $page->generateSeoDescription($customText);

// Generate for specific language
$description = $page->generateSeoDescription(null, 'de');
```

### Via API (Panel or External)

```bash
curl -X POST https://yourdomain.com/api/seo-ai/generate \
  -H "Authorization: Bearer YOUR-PANEL-TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "text": "Your content to summarize",
    "language": "de"
  }'
```

## Verification Checklist

- [ ] Dependencies installed via Composer
- [ ] API key configured in `config.php`
- [ ] Snippets added to template head
- [ ] Blueprint extended (optional)
- [ ] Meta tags visible in page source
- [ ] Sitemap accessible at `/sitemap.xml`
- [ ] Hreflang tags present (multilanguage sites)
- [ ] OpenGraph tags present
- [ ] Twitter Card tags present

## Troubleshooting

### No meta tags appear

1. Check snippets are called in template
2. Verify plugin is loaded (check Panel plugins)
3. Clear cache: `site/cache/`

### AI generation fails

1. Verify API key is correct
2. Check OpenRouter account has credits
3. Check logs: `site/logs/`
4. Verify internet connection

### Sitemap empty

1. Check pages are listed (not drafts)
2. Verify exclude patterns in config
3. Check noIndex field on pages

### Multilanguage issues

1. Verify `'languages' => true` in config
2. Check language files exist
3. Verify language codes are correct

## Performance Optimization

### Cache Generated Descriptions

If you're generating descriptions on every page load, consider caching:

```php
// In template or controller
$description = $page->metaDescription()->isNotEmpty()
    ? $page->metaDescription()
    : $page->generateSeoDescription();

// Save to page if generated
if (!$page->metaDescription()->isNotEmpty() && $description) {
    $page->update(['metaDescription' => $description]);
}
```

### Use Auto-Generate Sparingly

Only enable auto-generate if you need it:

```php
'autoGenerate' => false, // Recommended
```

Instead, generate descriptions manually when needed.

## Next Steps

1. **Customize Blueprints** - Adjust fields to your needs
2. **Set Up Social Images** - Add default OG images
3. **Configure Sitemap** - Exclude unwanted pages
4. **Test Social Sharing** - Use Facebook/Twitter debuggers
5. **Monitor AI Usage** - Check OpenRouter dashboard

## Support

- Documentation: See `README.md`
- Issues: Check plugin logs
- API Docs: [OpenRouter Documentation](https://openrouter.ai/docs)
