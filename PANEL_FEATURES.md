# Panel Features Guide

This guide explains the panel features added to the kirby-seo-ai plugin.

## Overview

The plugin adds two powerful features to the Kirby panel:

1. **SEO Preview Section** - Live preview of how pages appear on search engines and social media
2. **AI Description Generator** - One-click button to generate meta descriptions using AI

## SEO Preview Section

### What It Does

Shows real-time previews of your page as it will appear on:

- **Google Search Results**
  - Title (blue, clickable link)
  - URL (green, below title)
  - Description (gray text)

- **Twitter Card**
  - Featured image (if set)
  - Title
  - Description (truncated to 140 chars)
  - Domain

- **Facebook Share**
  - Featured image (if set)
  - Title
  - Description (truncated to 120 chars)
  - Domain (uppercase)

### How It Works

The preview section is automatically added to any blueprint that extends `seo-ai/page`. It reads the current values from:
- `metaTitle` field
- `metaDescription` field
- `ogImage` field
- Current page URL

The preview updates as you type in the panel fields!

### Blueprint Usage

```yaml
# In your page blueprint
tabs:
  seo:
    extends: seo-ai/page
```

The preview section is automatically included at the top of the SEO tab.

## AI Description Generator

### What It Does

Generates SEO-optimized meta descriptions with a single button click:

1. Reads content from your page's `text` field
2. Detects the current language
3. Sends content to OpenRouter API with Mistral AI
4. Generates a compelling 160-character description
5. Automatically fills the `metaDescription` field

### Features

- ✅ Language-aware (German, English, French, Spanish, Italian)
- ✅ Optimized for SEO (keywords, readability)
- ✅ Character limit enforcement (160 chars max)
- ✅ Loading state with spinner
- ✅ Success/error feedback
- ✅ No page reload needed

### Blueprint Usage

The AI generator button is automatically included in the `seo-ai/page` blueprint:

```yaml
fields:
  aiGenerator:
    type: seo-ai-generator
    label: AI Description Generator
    help: Generate a meta description automatically using AI
    sourceField: text        # Field to read content from
    targetField: metaDescription  # Field to write result to
```

### Customization

You can customize the source and target fields:

```yaml
aiGenerator:
  type: seo-ai-generator
  sourceField: myContent     # Read from custom field
  targetField: myDescription # Write to custom field
```

## Technical Details

### Files Created

- `src/index.js` - Vue.js components for panel UI
- `src/index.css` - Styling for previews
- `sections/preview.php` - Backend for preview section
- `package.json` - Package metadata

### API Endpoint

The generator uses the following API endpoint:

```
POST /api/seo-ai/generate
Authorization: Bearer <panel-token>
Content-Type: application/json

{
  "text": "Page content here...",
  "language": "de"
}
```

Response:

```json
{
  "status": "success",
  "description": "Generated description here..."
}
```

### Browser Compatibility

The panel features work in all modern browsers:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

## Troubleshooting

### Preview Not Showing

1. Make sure you've extended the blueprint: `extends: seo-ai/page`
2. Clear the panel cache
3. Check browser console for errors

### AI Button Not Working

1. Verify API key is configured in `config.php`
2. Check that the `text` field exists and has content
3. Look at the browser console for error messages
4. Check Kirby logs: `site/logs/`

### Preview Not Updating

1. Make sure JavaScript is enabled
2. Check that fields are named correctly: `metaTitle`, `metaDescription`, `ogImage`
3. Try refreshing the panel page

## Styling Customization

You can customize the preview styling by overriding the CSS classes:

```css
/* In your custom plugin or CSS file */
.k-google-preview__title {
  color: #1a0dab; /* Google blue */
}

.k-twitter-preview {
  border-radius: 16px; /* Twitter style */
}

.k-facebook-preview__body {
  background: #f0f2f5; /* Facebook gray */
}
```

## Performance

- Preview updates are instant (no API calls)
- AI generation takes 1-3 seconds (depending on API response)
- No impact on frontend performance
- Assets are loaded only in the panel

## Accessibility

The panel features are fully accessible:
- Keyboard navigation supported
- Screen reader friendly
- Proper ARIA labels
- Focus management

## Future Enhancements

Potential features for future versions:
- LinkedIn preview
- Instagram preview
- Real-time character counter
- Bulk AI generation
- Custom AI prompts
- Preview for different languages
