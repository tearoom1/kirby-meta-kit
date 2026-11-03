<?php

/**
 * Meta Kit Hooks
 *
 * Defines hooks for Meta Kit plugin
 */

return array_merge_recursive([
    "system.loadPlugins:after" => function () {
        // Initialize site SEO block fields on first load if they don't exist
        $site = site();
        $needsUpdate = false;
        $updates = [];

        // The settings blocks are shared by all languages: check and write
        // the default language, whatever language is current

        if (TearoomOne\ConfigHelper::siteSetting("metaKitOpenrouter")->isEmpty()) {
            $updates["metaKitOpenrouter"] = [
                [
                    "content" => [
                        "apiKey" => "",
                        "model" => "google/gemma-4-31b-it:free",
                        "temperature" => 0.7,
                    ],
                    "id" => "openrouter-settings",
                    "isHidden" => false,
                    "type" => "mk-openrouter",
                ],
            ];
            $needsUpdate = true;
        }

        if (TearoomOne\ConfigHelper::siteSetting("metaKitSitemap")->isEmpty()) {
            $updates["metaKitSitemap"] = [
                [
                    "content" => [
                        "exclude" => [],
                        "priorityHome" => 1.0,
                        "priorityDefault" => 0.8,
                    ],
                    "id" => "sitemap-settings",
                    "isHidden" => false,
                    "type" => "mk-sitemap",
                ],
            ];
            $needsUpdate = true;
        }

        if (TearoomOne\ConfigHelper::siteSetting("metaKitRobots")->isEmpty()) {
            $updates["metaKitRobots"] = [
                [
                    "content" => [
                        "enabled" => true,
                        "defaultRules" => true,
                        "includeSitemap" => true,
                        "blockBadBots" => false,
                        "customRules" => [],
                        "customDirectives" => "",
                    ],
                    "id" => "robots-settings",
                    "isHidden" => false,
                    "type" => "mk-robots",
                ],
            ];
            $needsUpdate = true;
        }

        // Only update if needed and not already being updated
        // Guard against running twice; a static instead of a constant keeps
        // each loaded copy of this hook independent (tests)
        static $initializing = false;

        if ($needsUpdate && !$initializing) {
            $initializing = true;
            $kirby = kirby();
            $previousLanguage = $kirby->language()?->code();
            $defaultLanguage = $kirby->defaultLanguage()?->code();

            try {
                // Blocks serialize their nested fields in the current
                // language, which would drop the shared ones outside the
                // default language
                if ($defaultLanguage !== null) {
                    $kirby->setCurrentLanguage($defaultLanguage);
                }

                // Scoped impersonation: the rest of the request must keep
                // running as the actual visitor, even if the update fails
                $kirby->impersonate("kirby", fn () => $site->update($updates, $defaultLanguage));
            } catch (\Exception $e) {
                // Silently fail - site might be read-only or in a context where updates aren't allowed
            } finally {
                if ($previousLanguage !== null) {
                    $kirby->setCurrentLanguage($previousLanguage);
                }
            }
        }
    },

    "page.update:after" => function ($newPage, $oldPage) {
        // Auto-generate description if enabled and field is empty
        $autoGenerate = option("tearoom1.meta-kit.autoGenerate", false);

        if (
            !$autoGenerate ||
            !TearoomOne\MetaKit::isAiEnabled() ||
            $newPage->intendedTemplate()->name() === "error" ||
            // Only the current language counts, not a fallback translation
            TearoomOne\MetaKitController::hasFieldInCurrentLanguage($newPage, "metaDescription") ||
            !TearoomOne\MetaKitController::hasEnoughContent($newPage)
        ) {
            return;
        }

        $pageId = $newPage->id();
        $languageCode = kirby()->language()?->code();

        // Generate after the response so saving isn't blocked by the API call;
        // the generator's own update re-enters this hook and stops above
        TearoomOne\MetaKit::afterResponse(function () use ($pageId, $languageCode) {
            $result = TearoomOne\MetaKitController::generateField(
                $pageId,
                "metaDescription",
                $languageCode,
                true
            );

            if (($result["status"] ?? null) !== "success") {
                TearoomOne\MetaKit::log(
                    "Meta Kit auto-generate error: " . ($result["message"] ?? "unknown error")
                );
            }
        });
    },
    "site.update:after" => function ($newSite) {
        // Copies of shared settings left in translation files would still
        // show in the Panel of that language
        TearoomOne\ConfigHelper::removeSharedSiteCopies($newSite);
        TearoomOne\ConfigHelper::clearCache();
    },
    // Keep old URLs working with 301 redirects
    "page.changeSlug:after" => function ($newPage, $oldPage) {
        TearoomOne\Redirects::record($newPage, $oldPage);
    },
    "page.move:after" => function ($newPage, $oldPage) {
        TearoomOne\Redirects::record($newPage, $oldPage);
    },
    "route:after" => function ($route, $path, $method, $result, $final) {
        if ($final !== true || $result !== null || $method !== "GET") {
            return $result;
        }

        $url = TearoomOne\Redirects::find(kirby()->request()->path()->toString());
        return $url ? Kirby\Http\Response::redirect($url, 301) : $result;
    },
],
// Content changes can alter the sitemap and llms.txt. Only real change
// events: a wildcard like "page.*:after" also matches "page.render:after"
// and would flush the cache on every page view. Events that already have a
// handler above get both callbacks.
array_fill_keys(
    array_map(fn ($event) => $event . ":after", TearoomOne\Sitemap::FLUSH_EVENTS),
    function () {
        TearoomOne\Sitemap::flushCache();
    }
));
