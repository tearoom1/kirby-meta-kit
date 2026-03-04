<?php

/**
 * Meta Kit API Routes
 *
 * Defines all API routes for Meta Kit plugin
 */

return function () {
    $licenseGuard = function () {
        if (!TearoomOne\MetaKit::hasValidLicense()) {
            return [
                'status' => 'error',
                'message' => 'A valid license is required to use AI generation. Please activate your license.'
            ];
        }

        return null;
    };

    $routes = [
        [
            "pattern" => "meta-kit/pages",
            "method" => "GET",
            "auth" => true,
            "action" => function () {
                $data = TearoomOne\MetaKitController::getPages();
                return [
                    "status" => "success",
                    "data" => $data["pages"],
                    "language" => $data["language"],
                    "languages" => $data["languages"],
                    "aiEnabled" => $data["aiEnabled"],
                    "validationSettings" => $data["validationSettings"] ?? [],
                ];
            },
        ],
        [
            "pattern" => "meta-kit/apply-single-field",
            "method" => "POST",
            "auth" => true,
            "action" => function () {
                if ($error = $licenseGuard()) {
                    $error['message'] = 'A valid license is required to save changes. Please activate your license.';
                    return $error;
                }

                $pageId = get("pageId");
                $fieldName = get("fieldName");
                $value = get("value");
                return TearoomOne\MetaKitController::applySingleField(
                    $pageId,
                    $fieldName,
                    $value,
                );
            },
        ],
        [
            "pattern" => "meta-kit/pages-with-content",
            "method" => "GET",
            "auth" => true,
            "action" => function () {
                return TearoomOne\MetaKitController::getPagesWithContent();
            },
        ],
        [
            "pattern" => "meta-kit/single-page",
            "method" => "GET",
            "auth" => true,
            "action" => function () {
                $pageId = get("pageId");
                return TearoomOne\MetaKitController::getSinglePage($pageId);
            },
        ],
    ];

    // Add AI-related routes only if AI is enabled
    if (TearoomOne\MetaKit::isAiEnabled()) {
        $routes[] = [
            "pattern" => "meta-kit/generate",
            "method" => "POST",
            "auth" => true,
            "action" => require __DIR__ . "/generate.php",
        ];
        $routes[] = [
            "pattern" => "meta-kit/generate-description",
            "method" => "POST",
            "auth" => true,
            "action" => function () {
                if ($error = $licenseGuard()) {
                    return $error;
                }

                $pageId = get("pageId");
                return TearoomOne\MetaKitController::generateDescription(
                    $pageId,
                );
            },
        ];
        $routes[] = [
            "pattern" => "meta-kit/generate-all",
            "method" => "POST",
            "auth" => true,
            "action" => function () {
                if ($error = $licenseGuard()) {
                    return $error;
                }

                $generateTitle = get("generateTitle", false);
                $generateDescription = get("generateDescription", false);
                $generateOgTitle = get("generateOgTitle", false);
                $generateOgDescription = get("generateOgDescription", false);
                $pageIds = get("pageIds", []);

                return TearoomOne\MetaKitController::generateAllFields(
                    $generateTitle,
                    $generateDescription,
                    $generateOgTitle,
                    $generateOgDescription,
                    $pageIds
                );
            },
        ];
        $routes[] = [
            "pattern" => "meta-kit/generate-field",
            "method" => "POST",
            "auth" => true,
            "action" => function () {
                if ($error = $licenseGuard()) {
                    return $error;
                }

                $pageId = get("pageId");
                $fieldName = get("fieldName");
                $language = get("language");
                return TearoomOne\MetaKitController::generateField(
                    $pageId,
                    $fieldName,
                    $language,
                );
            },
        ];
    }

    // Add legacy migration routes only if enabled
    if (option("tearoom1.meta-kit.legacyMigration", false)) {
        // Summary across all languages (default first)
        $routes[] = [
            "pattern" => "meta-kit/legacy-summary",
            "method" => "GET",
            "auth" => true,
            "action" => function () {
                return TearoomOne\LegacyMigration::detectLegacySummaryAllLanguages();
            },
        ];
        // Convert for all languages (default first)
        $routes[] = [
            "pattern" => "meta-kit/convert-legacy-all-languages",
            "method" => "POST",
            "auth" => true,
            "action" => function () {
                return TearoomOne\LegacyMigration::convertAllLanguages();
            },
        ];
    }

    return $routes;
};
