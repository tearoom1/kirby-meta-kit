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

    $reviewGuard = function () {
        if (!TearoomOne\MetaKit::isReviewEnabled()) {
            return [
                'status' => 'error',
                'message' => 'AI review is disabled. Enable it in the plugin options and make sure AI is configured.'
            ];
        }

        return null;
    };

    $reviewPageGuard = function (string $pageId) use ($reviewGuard) {
        if ($error = $reviewGuard()) {
            return $error;
        }

        if (!TearoomOne\MetaKit::canReviewPage($pageId)) {
            return [
                'status' => 'error',
                'message' => 'Without a valid license, AI content review is limited to root-level pages.'
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
                    "reviewEnabled" => $data["reviewEnabled"],
                    "siteSettings" => $data["siteSettings"] ?? [],
                    "validationSettings" => $data["validationSettings"] ?? [],
                ];
            },
        ],
        [
            "pattern" => "meta-kit/apply-single-field",
            "method" => "POST",
            "auth" => true,
            "action" => function () use ($licenseGuard) {
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
    if (TearoomOne\MetaKit::isReviewEnabled()) {
        $routes[] = [
            "pattern" => "meta-kit/review-page",
            "method" => "POST",
            "auth" => true,
            "action" => function () use ($reviewPageGuard) {
                $pageId = get("pageId");
                if ($error = $reviewPageGuard($pageId)) {
                    return $error;
                }

                return TearoomOne\SeoReview::reviewPage($pageId);
            },
        ];
    }

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
            "action" => function () use ($licenseGuard) {
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
            "action" => function () use ($licenseGuard) {
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
            "action" => function () use ($licenseGuard) {
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

    return $routes;
};
