<?php

namespace TearoomOne;

class SeoReview
{
    public static function reviewPage(string $pageId): array
    {
        $page = MetaKitController::getReviewContext($pageId, 12000);
        if (!$page) {
            return ApiResponse::notFound();
        }

        $review = (new MetaKit(kirby()))->generateSeoReview([
            'scope' => 'page',
            'page' => $page,
        ]);

        return ApiResponse::success([
            'scope' => 'page',
            'page' => [
                'id' => $page['id'],
                'title' => $page['title'],
                'panelUrl' => $page['panelUrl'],
            ],
            'review' => $review,
        ]);
    }

    public static function reviewPageIds(array $pageIds): array
    {
        $pages = MetaKitController::getReviewContexts($pageIds, 20, 1600);
        $review = (new MetaKit(kirby()))->generateSeoReview([
            'scope' => 'selection',
            'pages' => $pages,
        ]);

        return ApiResponse::success([
            'scope' => 'selection',
            'pages' => array_map(fn ($page) => [
                'id' => $page['id'],
                'title' => $page['title'],
                'panelUrl' => $page['panelUrl'],
            ], $pages),
            'review' => $review,
        ]);
    }

    public static function reviewSite(): array
    {
        $pageIds = array_map(fn ($page) => $page['id'], MetaKitController::getPages()['pages'] ?? []);
        $pages = MetaKitController::getReviewContexts($pageIds, 25, 1200);

        $review = (new MetaKit(kirby()))->generateSeoReview([
            'scope' => 'site',
            'pages' => $pages,
        ]);

        return ApiResponse::success([
            'scope' => 'site',
            'pagesReviewed' => count($pages),
            'review' => $review,
        ]);
    }
}
