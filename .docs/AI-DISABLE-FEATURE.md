# AI Integration Disable Feature

## Overview

Added the ability to disable AI integration in the Meta Kit plugin, both explicitly via config and automatically when no API credentials are provided.

## Changes Made

### 1. Backend - Core Logic

**`src/MetaKit.php`**
- Added `isAiEnabled()` static method that checks:
  - Config option `tearoom1.meta-kit.ai.enabled` (explicit disable)
  - Presence of API key (config or site settings)
  - Presence of model (config or site settings)
  - Returns `false` if explicitly disabled OR if both key and model are empty

### 2. API Routes

**`index.php`**
- AI-related routes now conditionally registered only when `MetaKit::isAiEnabled()` returns `true`
- Routes affected:
  - `meta-kit/generate`
  - `meta-kit/generate-description`
  - `meta-kit/generate-all`
  - `meta-kit/generate-field`
- Non-AI routes (pages list, field updates) remain always available

### 3. Page & Field Methods

**`index.php`**
- `generateSeoDescription()` page method: Returns `null` if AI disabled
- `toSeoDescription()` field method: Returns `null` if AI disabled
- Auto-generate hook: Skips processing if AI disabled

### 4. Controller & Frontend Props

**`src/MetaKitController.php`**
- Added `aiEnabled` to `getPages()` return array
- Passed to frontend via `MetaKitController::getPages()`

**`src/areas/meta-kit.php`**
- Added `aiEnabled` prop to Meta Kit panel view
- Frontend JavaScript can now conditionally show/hide AI features

### 5. Configuration

**`index.php`**
- Added new option: `'ai.enabled' => true` (default)

**`site/config/config.php`**
- Added `'ai.enabled' => true` with documentation comments
- Updated comments for `api.model` to indicate auto-disable behavior

**`config.example.php`**
- Added AI Integration section with `ai.enabled` option
- Updated documentation for all AI-related options

### 6. Panel UI

**`blueprints/site.yml`**
- Added info field that appears when OpenRouter settings are empty
- Informs users about AI integration status

**`blueprints/blocks/mk-openrouter.yml`**
- Added info field at top explaining AI integration
- Updated help text for API key field to mention disabling behavior

### 7. Frontend Panel Field

**`js/index.js`**
- Updated `meta-kit-generator` field component
- Added `created()` lifecycle hook to check AI status via API
- Added conditional rendering:
  - Shows info box when AI disabled
  - Hides generate button when AI disabled
  - Displays helpful message directing users to settings
- Field now checks AI status on load

### 8. Documentation

**`README.md`**
- Added `ai.enabled` to configuration examples
- Added "Disabling AI Features" section with:
  - Explicit disable method
  - Auto-disable behavior explanation
  - What happens when AI is disabled
- Updated key options documentation

## Usage

### Explicitly Disable AI

```php
'tearoom1.meta-kit' => [
    'ai.enabled' => false,
]
```

### Auto-Disable (Empty Credentials)

```php
'tearoom1.meta-kit' => [
    'api.key' => '',
    'api.model' => '',
]
```

### Check Status in Code

```php
if (\TearoomOne\MetaKit::isAiEnabled()) {
    // AI features available
} else {
    // AI disabled, show manual entry only
}
```

## Frontend Integration

The `aiEnabled` boolean is now passed as a prop to the panel component:

```javascript
// In meta-kit-view component
props: {
    aiEnabled: Boolean,
    // ... other props
}

// Use it to conditionally render AI buttons
<button v-if="aiEnabled">Generate with AI</button>
```

## Benefits

1. **Performance**: No API routes registered when AI disabled
2. **Clarity**: Clear indication in panel when AI unavailable
3. **Flexibility**: Can disable via config or by removing credentials
4. **Graceful**: Plugin still works for manual SEO management
5. **Safe**: Page methods return `null` instead of errors

## Backward Compatibility

- Default value is `true`, maintaining existing behavior
- Existing configs without this option will continue to work
- Auto-disable only triggers when BOTH key and model are empty
- All non-AI features remain fully functional
