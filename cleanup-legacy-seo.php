#!/usr/bin/env php
<?php

/**
 * CLI Script to clean up legacy SEO fields
 *
 * Usage: php bin/migrate-legacy-seo.php
 */

// Bootstrap Kirby
$base = dirname(__DIR__, 3);
include $base . '/kirby/bootstrap.php';

$kirby = new Kirby([
    'roots' => [
        'index'    => $base . '/public',
        'base'     => $base,
        'content'  => $base . '/content',
        'site'     => $base . '/site',
        'storage'  => $storage = $base . '/storage',
        'accounts' => $storage . '/accounts',
        'cache'    => $storage . '/cache',
        'sessions' => $storage . '/sessions',
    ]
]);

// authenticate as almighty
$kirby->impersonate('kirby');

// Import the LegacyMigration class
use TearoomOne\LegacyCleanup;

// Colors for CLI output
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[0;32m",
    'yellow' => "\033[0;33m",
    'red' => "\033[0;31m",
    'blue' => "\033[0;34m",
    'cyan' => "\033[0;36m",
    'bold' => "\033[1m",
];

function colored($text, $color, $colors) {
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

// Start cleanup
echo colored("\n========================================\n", 'bold', $colors);
echo colored("Meta-Kit Legacy SEO Field Cleanup\n", 'bold', $colors);
echo colored("========================================\n\n", 'bold', $colors);

$pages = $kirby->site()->index();
$totalPages = $pages->count();
$languages = $kirby->languages();
$languageCodes = $languages ? $languages->codes() : [null];
$totalLanguages = count($languageCodes);

echo colored("Found {$totalPages} pages to process\n", 'cyan', $colors);
if ($languages) {
    echo colored("Languages: " . implode(', ', $languageCodes) . "\n", 'cyan', $colors);
}
echo colored("Starting cleanup...\n\n", 'cyan', $colors);

$cleaned = 0;
$skipped = 0;
$errors = 0;
$current = 0;
$totalOperations = $totalPages * $totalLanguages;

foreach ($pages as $page) {

    $pageId = $page->id();
    $pageTitle = $page->title()->value();

    echo colored("Processing: {$pageTitle}\n", 'bold', $colors);
    echo "  Path: " . colored($pageId, 'blue', $colors) . "\n";

    foreach ($languageCodes as $langCode) {
        $current++;

        // Set current language
        if ($langCode) {
            $kirby->setCurrentLanguage($langCode);
            echo colored("  [{$current}/{$totalOperations}] Language: {$langCode}\n", 'cyan', $colors);
        } else {
            echo colored("  [{$current}/{$totalOperations}]\n", 'cyan', $colors);
        }

        try {
            $result = LegacyCleanup::cleanupLegacyFields($pageId);

            if ($result['status'] === 'success') {
                $cleaned++;
                echo colored("    ✓ Success: ", 'green', $colors) . $result['message'] . "\n";
            } elseif ($result['status'] === 'info') {
                $skipped++;
                echo colored("    ⓘ Skipped: ", 'yellow', $colors) . $result['message'] . "\n";
            } else {
                $errors++;
                echo colored("    ✗ Error: ", 'red', $colors) . $result['message'] . "\n";
            }
        } catch (\Exception $e) {
            $errors++;
            echo colored("    ✗ Exception: ", 'red', $colors) . $e->getMessage() . "\n";
        }
    }

    echo "\n";
}

// Summary
echo colored("========================================\n", 'bold', $colors);
echo colored("Cleanup Complete!\n", 'bold', $colors);
echo colored("========================================\n\n", 'bold', $colors);

echo colored("Total pages: ", 'cyan', $colors) . $totalPages . "\n";
echo colored("Total languages: ", 'cyan', $colors) . $totalLanguages . "\n";
echo colored("Total operations: ", 'cyan', $colors) . $totalOperations . "\n\n";
echo colored("✓ Successfully cleaned: ", 'green', $colors) . $cleaned . "\n";
echo colored("ⓘ Skipped (no changes): ", 'yellow', $colors) . $skipped . "\n";
echo colored("✗ Errors: ", 'red', $colors) . $errors . "\n\n";

if ($errors > 0) {
    echo colored("⚠ Some pages had errors. Please review the output above.\n", 'yellow', $colors);
    exit(1);
} else {
    echo colored("✓ All pages processed successfully!\n", 'green', $colors);
    exit(0);
}
