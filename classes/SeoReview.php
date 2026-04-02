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
}
