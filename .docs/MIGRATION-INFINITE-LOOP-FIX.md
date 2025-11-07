# Migration Infinite Loop Fix

## Problem Identified

The migration was running into an **infinite loop** caused by the `page.update:after` hook:

1. Migration updates a page with legacy fields
2. Hook `page.update:after` triggers
3. Hook tries to auto-generate description
4. **Hook updates the page again**
5. Hook triggers again → **INFINITE LOOP**

## Solution Implemented

Added a **migration flag** to prevent hooks from running during migration:

### Changes Made

1. **`src/LegacyMigration.php`**
   - Added `private static $isMigrating` flag
   - Added `public static function isMigrating(): bool` method
   - Set flag to `true` at start of `convertAllLegacyFields()`
   - Reset flag to `false` in `finally` block (ensures it always resets)

2. **`index.php`** - Hook modification
   - Added check at start of `page.update:after` hook:
   ```php
   if (\TearoomOne\LegacyMigration::isMigrating()) {
       return; // Skip hook during migration
   }
   ```

3. **Route Error Handling** - `index.php`
   - Added output buffer cleaning
   - Added try-catch with `\Throwable` for fatal errors
   - Added array validation
   - Ensures clean JSON response always returned

4. **Improved Error Messages** - `src/LegacyMigration.php`
   - Collects error messages from individual page failures
   - Returns first 5 errors in response message
   - Continues processing other pages even if one fails
   - Never throws uncaught exceptions

5. **Simplified Frontend** - `js/components/MetaKitView.vue`
   - Removed complex loading notification logic (causing issues)
   - Added console logging for debugging
   - Simplified error handling
   - Proper null checking with `response?.property`

## How It Works Now

### Migration Flow
```
1. User clicks "Migrate All"
2. LegacyMigration::$isMigrating = true
3. For each page:
   - convertLegacyMetadata(pageId)
   - page.update() is called
   - page.update:after hook checks isMigrating() → returns early
   - No recursive updates
4. Migration completes
5. LegacyMigration::$isMigrating = false (in finally block)
```

### Error Handling
- Individual page failures don't stop the entire migration
- All errors are collected and reported
- First 5 errors shown in success message
- Always returns valid JSON to prevent panel errors

## Testing

After this fix, migration should:
- ✅ Complete without infinite loops
- ✅ Process all pages even if some fail
- ✅ Return detailed results (converted, skipped, errors)
- ✅ Not trigger auto-generate hook during migration
- ✅ Show specific error messages for failed pages

## Try It Now

1. Hard refresh browser: `Cmd + Shift + R`
2. Open browser console (F12) to see logs
3. Click "Migrate All" button
4. Should complete successfully with counts displayed

## Expected Console Output

```
Starting migration...
Response received: {
  status: "success",
  message: "Migrated 15 pages, skipped 3, errors 0",
  converted: 15,
  skipped: 3,
  errors: 0
}
```

## If Still Having Issues

Check console for:
- Network errors (timeout, 500, etc.)
- PHP errors in response
- JavaScript errors in migration method

The console.log statements will show exactly where it's failing.
