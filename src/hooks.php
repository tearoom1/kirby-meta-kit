<?php

/**
 * Meta Kit Hooks
 *
 * Defines hooks for Meta Kit plugin
 */

return [
    "system.loadPlugins:after" => function () {
        // Initialize site SEO objects on first load if they don't exist
        $site = site();
        $needsUpdate = false;
        $updates = [];

        // Check if objects need initialization
        if ($site->metaKitSeo()->isEmpty()) {
            $updates["metaKitSeo"] = [
                [
                    "content" => [
                        "appendSiteName" => true,
                        "titleSeparator" => "|",
                        "metaTitle" => "",
                        "metaDescription" => "",
                        "metaKeywords" => "",
                        "ogImage" => [],
                    ],
                    "id" => "site-seo-settings",
                    "isHidden" => false,
                    "type" => "mk-site-seo",
                ],
            ];
            $needsUpdate = true;
        }

        if ($site->metaKitOpenrouter()->isEmpty()) {
            $updates["metaKitOpenrouter"] = [
                [
                    "content" => [
                        "apiKey" => "",
                        "model" => "google/gemini-2.0-flash-exp:free",
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
            // Check if SEO object exists and if metadescription is empty
            $seoData = $newPage->metaKitSeo()->toObject();
            if (!$seoData || $seoData->metadescription()->isNotEmpty()) {
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
                    // Get existing SEO data and update metadescription
                    $existingSeo = $newPage->metaKitSeo()->yaml();
                    $existingSeo["metadescription"] = $description;

                    $newPage->update(
                        [
                            "metaKitSeo" => $existingSeo,
                        ],
                        kirby()->language()?->code(),
                    );
                }
            }
        } catch (Exception $e) {
            // Silently fail - don't break the save operation
            kirbylog("Meta Kit auto-generate error: " . $e->getMessage());
        }
    },
];
