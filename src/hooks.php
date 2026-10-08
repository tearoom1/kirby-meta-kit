<?php

/**
 * Meta Kit Hooks
 *
 * Defines hooks for Meta Kit plugin
 */

return [
    "system.loadPlugins:after" => function () {
        // Initialize site SEO block fields on first load if they don't exist
        $site = site();
        $needsUpdate = false;
        $updates = [];

        // Site SEO settings are now flat fields, no initialization needed

        if ($site->metaKitOpenrouter()->isEmpty()) {
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

        if ($site->metaKitSitemap()->isEmpty()) {
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

        if ($site->metaKitRobots()->isEmpty()) {
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
        if ($needsUpdate && !defined("KIRBY_META_KIT_INITIALIZING")) {
            define("KIRBY_META_KIT_INITIALIZING", true);
            try {
                // Scoped impersonation: the rest of the request must keep
                // running as the actual visitor, even if the update fails
                kirby()->impersonate("kirby", fn () => $site->update($updates));
            } catch (\Exception $e) {
                // Silently fail - site might be read-only or in a context where updates aren't allowed
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
    "site.update:after" => function () {
        TearoomOne\ConfigHelper::clearCache();
    },
];
