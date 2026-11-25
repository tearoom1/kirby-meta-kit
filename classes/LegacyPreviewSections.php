<?php

namespace TearoomOne;

use Kirby\Blueprint\Blueprint;
use Kirby\Cms\App;
use Kirby\Data\Data;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Throwable;

/**
 * Blueprints of Meta Kit 4 placed the preview in a `sections` list. Kirby 5
 * kept sections and fields apart, so such a section could share its name
 * with a content field. Kirby 6 converts sections into fields with one
 * namespace, and the second of both names becomes an error field.
 *
 * Before Kirby reads them, every `seo-preview` section in the site's
 * blueprints is renamed to `metaKitSection<Name>`, a stable name of its own.
 * The preview has no content, so nothing stored depends on the old name.
 */
class LegacyPreviewSections
{
    public const PREFIX = 'metaKitSection';

    /**
     * Put the renamed blueprints into Kirby's blueprint cache. Only files
     * that mention `seo-preview` are parsed; site/blueprints wins over
     * plugin blueprints, as in Kirby's own lookup.
     */
    public static function preload(App $kirby): void
    {
        foreach (self::candidates($kirby) as $name => $source) {
            if (isset(Blueprint::$loaded[$name]) === true) {
                continue;
            }

            try {
                $props = is_array($source) ? $source : Data::read($source);
                $renamed = self::rename($props);

                if ($renamed !== $props) {
                    Blueprint::$loaded[$name] = $renamed;
                }
            } catch (Throwable) {
                // Leave a blueprint that cannot be read to Kirby's own error
            }
        }
    }

    /**
     * Name of the field a preview section becomes
     */
    public static function fieldName(string $section): string
    {
        return self::PREFIX . ucfirst($section);
    }

    /**
     * Rename preview sections at every level that can hold sections
     */
    public static function rename(array $props): array
    {
        if (is_array($props['sections'] ?? null) === true) {
            $sections = [];

            foreach ($props['sections'] as $name => $section) {
                $key = is_string($name) && self::isPreview($name, $section) ? self::fieldName($name) : $name;
                $sections[$key] = $section;
            }

            $props['sections'] = $sections;
        }

        foreach (['tabs', 'columns'] as $group) {
            if (is_array($props[$group] ?? null) === false) {
                continue;
            }

            foreach ($props[$group] as $key => $child) {
                if (is_array($child) === true) {
                    $props[$group][$key] = self::rename($child);
                }
            }
        }

        return $props;
    }

    private static function isPreview(string $name, mixed $section): bool
    {
        if (is_string($section) === true || (is_array($section) === true && isset($section['extends']) === true)) {
            $section = Blueprint::extend($section);
        }

        if (is_array($section) === false) {
            $section = [];
        }

        return ($section['type'] ?? $name) === 'seo-preview';
    }

    /**
     * Blueprints that may hold a preview section, by name: those that
     * mention `seo-preview`, or a blueprint that does (a section mixin)
     */
    private static function candidates(App $kirby): array
    {
        $sources = [];

        foreach ($kirby->extensions('blueprints') as $name => $source) {
            if (is_array($source) === true) {
                $sources[$name] = [$source, json_encode($source) ?: ''];
            } elseif (is_string($source) === true && is_file($source) === true) {
                $sources[$name] = [$source, (string)F::read($source)];
            }
        }

        $root = $kirby->root('blueprints');

        // A site file replaces a plugin blueprint of the same name
        if (is_string($root) === true && is_dir($root) === true) {
            foreach (Dir::index($root, true) as $file) {
                if (str_ends_with($file, '.yml') === true) {
                    $sources[substr($file, 0, -4)] = [$root . '/' . $file, (string)F::read($root . '/' . $file)];
                }
            }
        }

        $previews = array_keys(array_filter($sources, fn ($source) => str_contains($source[1], 'seo-preview')));
        $candidates = [];

        foreach ($sources as $name => [$source, $text]) {
            foreach (['seo-preview', ...$previews] as $needle) {
                if (str_contains($text, $needle) === true) {
                    $candidates[$name] = $source;
                    break;
                }
            }
        }

        return $candidates;
    }
}
