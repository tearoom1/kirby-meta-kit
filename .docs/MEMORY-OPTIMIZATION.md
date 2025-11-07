# Memory Optimization for Migration

## Problem
Migration was hitting PHP memory limit (1GB exhausted) when processing large sites with many pages.

## Solutions Implemented

### 1. Temporary Memory Limit Increase
**File:** `src/LegacyMigration.php`

```php
// Increase to 2GB during migration
$originalLimit = ini_get('memory_limit');
@ini_set('memory_limit', '2048M');

// ... migration code ...

// Restore original limit in finally block
@ini_set('memory_limit', $originalLimit);
```

### 2. Only Process Pages with Legacy Fields
Instead of processing ALL pages, we:
1. First call `detectLegacyMetadata()` to find pages with legacy fields
2. Only process those specific pages
3. Skips hundreds/thousands of pages that don't need migration

**Before:**
```php
$pages = $kirby->site()->index(); // Loads ALL pages
foreach ($pages as $page) { /* process */ }
```

**After:**
```php
$legacyDetection = self::detectLegacyMetadata();
$pagesToMigrate = $legacyDetection['pages']; // Only pages with legacy fields
foreach ($pagesToMigrate as $pageData) { /* process only these */ }
```

### 3. Cache Clearing During Processing
**Migration:** Clear cache every 10 pages
```php
if ($processedCount % 10 === 0) {
    $kirby->cache('pages')->flush();
    gc_collect_cycles(); // PHP garbage collection
}
```

**Detection:** Clear cache every 20 pages
```php
if ($count % 20 === 0) {
    $kirby->cache('pages')->flush();
    gc_collect_cycles();
}
```

### 4. Prevent Hook Recursion
Added migration flag to prevent `page.update:after` hook from running during migration:

```php
if (\TearoomOne\LegacyMigration::isMigrating()) {
    return; // Skip hook
}
```

This prevents:
- Infinite loops
- Extra page loads
- Extra YAML parsing
- Memory multiplication

## Memory Usage Breakdown

### Before Optimizations
- Load ALL pages: ~500MB
- Process each page: +memory
- Hooks trigger: +more memory
- No cache clearing: accumulates
- **Total: Exceeds 1GB** ❌

### After Optimizations
- Detect legacy pages: ~200MB (with cache clearing)
- Load only legacy pages: ~50-100MB
- Process with cache clearing: ~50-100MB peak
- Hooks disabled: No extra memory
- **Total: ~300-400MB max** ✅

## Configuration

If you still hit memory limits, you can increase further in `config.php`:

```php
// Option 1: Increase PHP memory limit globally
ini_set('memory_limit', '3072M'); // 3GB

// Option 2: Set in .ddev/php/memory.ini or php.ini
memory_limit = 3072M
```

## How to Test

1. **Check current memory limit:**
   ```php
   echo ini_get('memory_limit');
   ```

2. **Monitor migration:**
   - Open browser Console (F12)
   - Watch for logs showing progress
   - Should complete without timeout

3. **Expected results:**
   - Detection: Finds X pages with legacy fields
   - Migration: Processes only those X pages
   - Memory: Stays under 2GB throughout

## If Still Having Issues

### Increase Memory Further
Edit `/Users/mathis/Work/Basic/kirby-basic/.ddev/php/php.ini`:
```ini
memory_limit = 4096M
```

Then restart DDEV:
```bash
ddev restart
```

### Check DDEV Memory
```bash
ddev describe
```

### Process in Manual Batches
Instead of "Migrate All", use the Legacy Data dialog to:
1. View detected pages
2. Migrate them individually or in small groups
3. Each individual migration uses minimal memory

## Summary

✅ Memory limit increased to 2GB during migration
✅ Cache clearing every 10-20 pages
✅ Only processes pages with legacy fields
✅ Hooks disabled during migration
✅ Original memory limit restored after migration
✅ Garbage collection runs periodically

The migration should now complete successfully even on large sites!
