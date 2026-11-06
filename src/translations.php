<?php

use Kirby\Cms\App;

/**
 * Panel texts for every Panel language Kirby ships.
 *
 * Kirby falls back to the key itself for blueprint labels in a language
 * without the string, not to English. So every Panel language starts with
 * the English texts, and our own translations replace them where they exist.
 */

$read = fn (string $file): array => json_decode(file_get_contents($file), true) ?? [];

$english = $read(__DIR__ . '/../translations/en.json');
$translations = ['en' => $english];

// Panel languages of the Kirby installation (one JSON file each); Kirby
// loads plugins while it boots, so the instance and its roots exist then
$kirbyRoot = App::instance(lazy: true)?->root('i18n:translations')
    ?? dirname((new ReflectionClass(App::class))->getFileName(), 3) . '/i18n/translations';

foreach (glob($kirbyRoot . '/*.json') ?: [] as $file) {
    $translations[basename($file, '.json')] = $english;
}

// Our own translations, completed with English for missing keys
foreach (glob(__DIR__ . '/../translations/*.json') ?: [] as $file) {
    $translations[basename($file, '.json')] = [...$english, ...$read($file)];
}

return $translations;
