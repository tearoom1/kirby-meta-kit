# Developer Reference

[← Back to the README](../README.md)

Page methods, API endpoints, custom templates, and what to check when something does not show up.


## Page Methods

```php
// Generate AI content
$title = $page->generateSeoTitle();
$title = $page->generateSeoTitle($content, 'de');  // Custom content & language
$desc = $page->generateSeoDescription();
$desc = $page->generateSeoDescription($content, 'fr');

// Field to SEO conversion
$title = $page->text()->toSeoTitle();
$desc = $page->text()->toSeoDescription();
```

## API Endpoints

Generate descriptions via API:

```bash
POST /api/meta-kit/generate
Content-Type: application/json

{
  "text": "Your page content here",
  "language": "de",
  "pageId": "page-id-here",
  "fieldType": "description"
}
```

Response:
```json
{
  "status": "success",
  "content": "AI-generated content matching validation rules..."
}
```

## Custom Templates

Access metadata in your templates:

```php
<?php
// Access SEO flat fields directly
$metaTitle = $page->metaTitle()->value();
$metaDesc = $page->metaDescription()->value();
$ogTitle = $page->ogTitle()->value();
$ogDesc = $page->ogDescription()->value();

// Get OG image file
$ogImage = $page->ogImage()->toFile();
?>
```

## Troubleshooting

### AI Generation Not Working

1. Check API key is set correctly
2. Verify model is selected
3. Check `ai.enabled` is not set to `false`
4. Look for errors in Kirby debug mode
5. Check your provider account has free tier or credits
6. With `api.provider` set to `mistral` or `custom`, make sure the model ID matches that provider

### Validation Not Showing

1. Check config file syntax
2. Verify template name matches exactly
3. Clear Kirby cache
4. Check browser console for JS errors

### Sitemap Not Appearing

1. Verify `sitemap.enabled => true`
2. Check route is registered
3. Clear Kirby cache
4. Check .htaccess for conflicting rules

### Panel Table Empty

1. Check `excludeTemplates` and `excludeStatus` settings
2. Verify pages exist and are not drafts (unless drafts allowed)
3. Check user permissions
4. Look for PHP errors in logs
