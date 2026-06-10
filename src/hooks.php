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
                kirby()->impersonate("kirby");
                $site->update($updates);
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
            $newPage->intendedTemplate()->name() === "error"
        ) {
            return;
        }

        try {
            // Check if metaDescription is empty (flat field)
            if ($newPage->metaDescription()->isNotEmpty()) {
                return;
            }

            $content = $newPage->text()->toString();
            if (!empty($content)) {
                $metaKit = new TearoomOne\MetaKit(kirby());
                $languageCode = kirby()->language()?->code() ?? "en";
                $description = $metaKit->generateDescription($content, [
                    "language" => $languageCode,
                ]);

                if ($description) {
                    // Update flat field directly
                    $newPage->update(
                        [
                            "metaDescription" => $description,
                        ],
                        kirby()->language()?->code(),
                    );
                }
            }
        } catch (Exception $e) {
            // Silently fail - don't break the save operation
            TearoomOne\MetaKit::log("Meta Kit auto-generate error: " . $e->getMessage());
        }
    },
    "site.update:after" => function () {
        TearoomOne\ConfigHelper::clearCache();
    },
];
