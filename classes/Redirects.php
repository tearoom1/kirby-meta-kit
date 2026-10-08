<?php

namespace TearoomOne;

use Kirby\Cms\Page;
use Kirby\Data\Yaml;

/**
 * 301 redirects for pages whose URL changed (new slug or moved page).
 * Entries live in the site field `metaKitRedirects`, so editors can see and
 * remove them; the target is stored as page UUID, so renaming a page again
 * never creates redirect chains. Subpages are covered by the path prefix.
 */
class Redirects
{
    public static function isEnabled(): bool
    {
        return option('tearoom1.meta-kit.redirects.enabled', true) === true;
    }

    /**
     * Record a redirect for every language whose URL of the page changed
     */
    public static function record(Page $newPage, Page $oldPage): void
    {
        if (!self::isEnabled() || $newPage->isDraft()) {
            return;
        }

        $uuid = $newPage->uuid()?->toString();
        if (!$uuid) {
            return;
        }

        $kirby = kirby();
        $languageCodes = $kirby->multilang() ? $kirby->languages()->codes() : [null];
        $entries = self::entries();

        foreach ($languageCodes as $code) {
            $from = self::path($oldPage->url($code));
            $to = self::path($newPage->url($code));

            if ($from === $to) {
                continue;
            }

            // Drop entries the new URL now serves itself (renamed back)
            // and older entries for the same old URL
            $entries = array_values(array_filter(
                $entries,
                fn ($entry) => !in_array($entry['from'] ?? '', [$from, $to], true)
            ));

            $entries[] = [
                'from' => $from,
                'to' => [$uuid],
                'language' => $code ?? '',
                'created' => date('Y-m-d H:i'),
            ];
        }

        self::save($entries);
    }

    /**
     * Target URL for a requested path, or null
     */
    public static function find(string $path): ?string
    {
        if (!self::isEnabled()) {
            return null;
        }

        $path = '/' . trim($path, '/');

        foreach (self::entries() as $entry) {
            $from = $entry['from'] ?? '';
            if ($from === '' || ($path !== $from && !str_starts_with($path, rtrim($from, '/') . '/'))) {
                continue;
            }

            $target = kirby()->page($entry['to'][0] ?? '');
            if (!$target) {
                continue;
            }

            $url = $target->url(($entry['language'] ?? '') ?: null);
            return rtrim($url, '/') . substr($path, strlen(rtrim($from, '/')));
        }

        return null;
    }

    /**
     * URL path relative to the site root, e.g. "/en/journal/post"
     */
    public static function path(string $url): string
    {
        $base = rtrim((string)parse_url(kirby()->url('index'), PHP_URL_PATH), '/');
        $path = (string)parse_url($url, PHP_URL_PATH);

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return '/' . trim($path, '/');
    }

    public static function entries(): array
    {
        $field = kirby()->site()->content(kirby()->defaultLanguage()?->code())->get('metaKitRedirects');
        $entries = Yaml::decode($field->value() ?? '');

        return array_map(function ($entry) {
            // The pages field stores a list of UUIDs
            $entry['to'] = (array)($entry['to'] ?? []);
            return $entry;
        }, is_array($entries) ? $entries : []);
    }

    protected static function save(array $entries): void
    {
        $kirby = kirby();

        // Editors who may rename pages may not be allowed to edit the site
        $kirby->impersonate('kirby', fn () => $kirby->site()->update(
            ['metaKitRedirects' => Yaml::encode($entries)],
            $kirby->defaultLanguage()?->code()
        ));
    }
}
