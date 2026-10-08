<?php

namespace TearoomOne;

use Kirby\Toolkit\I18n;

/**
 * Translated plugin texts for PHP (API messages shown in the panel). Uses
 * the panel language Kirby sets for API requests; falls back to the English
 * texts when the plugin's translations aren't registered (e.g. in tests).
 * Placeholders use the `{name}` syntax, like in the panel JS.
 */
class Texts
{
    private static ?array $english = null;

    public static function get(string $key, array $data = []): string
    {
        $key = 'meta-kit.' . $key;
        self::$english ??= json_decode(file_get_contents(dirname(__DIR__) . '/translations/en.json'), true);

        $text = I18n::translate($key) ?? self::$english[$key] ?? $key;

        return preg_replace_callback(
            '/\{\s*(\w+)\s*\}/',
            fn ($match) => array_key_exists($match[1], $data) ? (string)$data[$match[1]] : $match[0],
            $text
        );
    }
}
