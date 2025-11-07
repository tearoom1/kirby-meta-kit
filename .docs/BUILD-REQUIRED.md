# Build Required After Changes

## JavaScript Changes Made

The following JavaScript files have been modified:
- `js/index.js` - Updated `meta-kit-generator` field component with AI disable check
- `js/components/MetaKitView.vue` - Multiple improvements:
  - Added `aiEnabled` prop and conditional rendering for AI buttons
  - Enhanced error handling for all operations
  - Added loading notifications for migration and bulk generation
  - Improved error messages with detailed information

## Build Instructions

Since the plugin uses **kirbyup** to build JavaScript assets, you need to rebuild after making changes:

### Development Build (with watch mode)
```bash
cd site/plugins/meta-kit
npm run dev
```

### Production Build
```bash
cd site/plugins/meta-kit
npm run build
```

## What Gets Built

The build process compiles:
- `js/index.js` → `index.js` (bundled with field component)
- `js/components/MetaKitView.vue` → Included in `index.js`
- `js/index.css` → `index.css`

## Note for Plugin Distribution

If you're distributing this plugin:
1. Run `npm run build` to create production assets
2. Commit the built files (`index.js`, `index.css`)
3. Users won't need to rebuild (built files are included)

## Verification

After building, check:
1. Panel loads without errors
2. Meta Kit area displays correctly
3. AI generator field shows info box when AI disabled
4. AI buttons hidden in Meta Kit view when disabled
