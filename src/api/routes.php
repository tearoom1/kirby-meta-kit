<?php

/**
 * Meta Kit API Routes
 *
 * Defines all API routes for Meta Kit plugin
 */

return function () {
    $accessGuard = function () {
        if (!TearoomOne\MetaKitController::canAccess()) {
            return \Kirby\Http\Response::json([
                'status' => 'error',
                'message' => 'Forbidden'
            ], 403);
        }

        return null;
    };

    $aiGuard = function () {
        if (!TearoomOne\MetaKit::isAiEnabled()) {
            return [
                'status' => 'error',
                'message' => TearoomOne\MetaKit::getAiAccessErrorMessage()
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

    $routes = [
        [
            "pattern" => "meta-kit/pages",
            "method" => "GET",
            "auth" => true,
            "action" => function () use ($accessGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }

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
            "action" => function () use ($accessGuard) {
                if ($error = $accessGuard()) {
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
            "action" => function () use ($accessGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }

                return TearoomOne\MetaKitController::getPagesWithContent();
            },
        ],
        [
            "pattern" => "meta-kit/single-page",
            "method" => "GET",
            "auth" => true,
            "action" => function () use ($accessGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }

                $pageId = get("pageId");
                return TearoomOne\MetaKitController::getSinglePage($pageId);
            },
        ],
    ];

    if (TearoomOne\MetaKit::isReviewEnabled()) {
        $routes[] = [
            "pattern" => "meta-kit/review-page",
            "method" => "POST",
            "auth" => true,
            "action" => function () use ($accessGuard, $reviewGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }
                if ($error = $reviewGuard()) {
                    return $error;
                }

                $pageId = get("pageId");
                return TearoomOne\SeoReview::reviewPage($pageId);
            },
        ];
    }

    if (TearoomOne\MetaKit::isAiEnabled()) {
        $routes[] = [
            "pattern" => "meta-kit/generate",
            "method" => "POST",
            "auth" => true,
            "action" => function () use ($accessGuard, $aiGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }
                if ($error = $aiGuard()) {
                    return $error;
                }

                $action = require __DIR__ . "/generate.php";
                return $action();
            },
        ];
        $routes[] = [
            "pattern" => "meta-kit/generate-description",
            "method" => "POST",
            "auth" => true,
            "action" => function () use ($accessGuard, $aiGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }
                if ($error = $aiGuard()) {
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
            "action" => function () use ($accessGuard, $aiGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }
                if ($error = $aiGuard()) {
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
            "action" => function () use ($accessGuard, $aiGuard) {
                if ($error = $accessGuard()) {
                    return $error;
                }
                if ($error = $aiGuard()) {
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
