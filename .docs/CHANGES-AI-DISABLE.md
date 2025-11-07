# AI Integration Disable Feature - Summary

## Overview
Added comprehensive AI disable functionality to the Meta Kit plugin, allowing users to disable AI features explicitly or automatically when credentials are missing.

## Files Modified

### Backend Files

1. **`src/MetaKit.php`**
   - Added `isAiEnabled()` static method
   - Checks config option, API key, and model availability
   - Returns `false` if explicitly disabled or credentials missing

2. **`src/MetaKitController.php`**
   - Added `aiEnabled` to `getPages()` return data
   - Passes AI status to frontend

3. **`src/areas/meta-kit.php`**
   - Added `aiEnabled` prop to Meta Kit view
   - Passes AI status to Vue component

4. **`index.php`**
   - Added `'ai.enabled' => true` option
   - Conditionally register AI routes only when enabled
   - Updated page and field methods to check AI status
   - Updated auto-generate hook to check AI status

### Configuration Files

5. **`site/config/config.php`**
   - Added `'ai.enabled' => true` option with comments
   - Updated comments to indicate auto-disable behavior

6. **`config.example.php`**
   - Added AI Integration section
   - Documented `ai.enabled` option
   - Updated all AI-related option comments

### Panel Blueprints

7. **`blueprints/site.yml`**
   - Added info field for AI disabled state
   - Shows when OpenRouter settings are empty

8. **`blueprints/blocks/mk-openrouter.yml`**
   - Added info field explaining AI integration
   - Updated help text for API key field

### Documentation

9. **`README.md`**
   - Added AI integration disable documentation
   - Added examples for explicit and auto-disable
   - Listed what happens when AI is disabled

### Frontend Files

10. **`js/components/MetaKitView.vue`**
    - Added `aiEnabled` prop
    - Conditionally show/hide AI buttons throughout:
      - "Generate Missing" button in actions bar
      - Individual "Generate" buttons in table
      - "AI Generate" buttons in Legacy Data dialog
      - "AI Generate" buttons in All Pages dialog
      - "AI Generate" buttons in Single Page Edit dialog

11. **`js/index.js`**
    - Updated `meta-kit-generator` field component
    - Added `aiEnabled` data property
    - Added `created()` lifecycle hook to fetch AI status
    - Modified template to show info box when AI disabled
    - Shows helpful message directing to settings when disabled

## Feature Behavior

### Explicit Disable
```php
'tearoom1.meta-kit' => [
    'ai.enabled' => false,
]
```

### Auto-Disable
AI features are automatically disabled when:
- Both `api.key` is empty AND
- `api.model` is empty

### When AI is Disabled

**Backend:**
- AI routes not registered (`/meta-kit/generate`, `/meta-kit/generate-field`, etc.)
- Page method `generateSeoDescription()` returns `null`
- Field method `toSeoDescription()` returns `null`
- Auto-generate hook skips AI processing

**Frontend:**
- All "AI Generate" buttons hidden in Meta Kit view
- "Generate Missing" bulk action hidden
- `meta-kit-generator` field shows info box instead of button
- Info message directs users to enable AI in settings
- Manual SEO field editing still available
- All other features (sitemap, manual metadata) work normally

## API Routes Status

### Always Available (AI disabled or enabled)
- `GET /meta-kit/pages` - List pages with SEO status
- `POST /meta-kit/apply-single-field` - Update SEO field manually
- `GET /meta-kit/pages-with-content` - Get pages with content
- `GET /meta-kit/single-page` - Get single page data

### Only When AI Enabled
- `POST /meta-kit/generate` - Generate description
- `POST /meta-kit/generate-description` - Generate for specific page
- `POST /meta-kit/generate-all` - Bulk generate
- `POST /meta-kit/generate-field` - Generate specific field

## Configuration Priority

Settings are merged with this priority (highest wins):
1. `site/config/config.php` settings (highest)
2. Site panel settings (middle)
3. Plugin defaults (lowest)

Both config and panel settings are checked for API key/model availability.

## User Experience

### With AI Enabled (Default)
- Full AI generation features available
- "AI Generate" buttons visible throughout
- Can use AI or manual entry

### With AI Disabled
- Clean interface without AI clutter
- Focus on manual SEO metadata entry
- Sitemap and other features unaffected
- No API calls or errors from missing credentials

## Testing Checklist

- [ ] Explicit disable works (`ai.enabled => false`)
- [ ] Auto-disable works (empty key/model)
- [ ] AI buttons hidden when disabled
- [ ] Manual editing still works
- [ ] Non-AI routes still accessible
- [ ] Panel loads without errors
- [ ] Sitemap generation unaffected
- [ ] Re-enabling AI restores functionality

## Migration Notes

**Existing installations:** No changes needed. The feature defaults to enabled (`ai.enabled => true`), maintaining current behavior.

**New installations:** Can disable AI by setting option or leaving credentials empty.

**No database changes:** This is a runtime configuration change only.
