<?php

use Kirby\Cms\Site;
use Kirby\Toolkit\I18n;
use TearoomOne\MetaKit;

return [
    'extends' => 'info',
    'props' => [
        'label' => function ($label = 'meta-kit.bp.meta-group.seoReview.label') {
            return I18n::translate($label, $label);
        },
        'theme' => function ($theme = 'info') {
            return $theme;
        }
    ],
    'computed' => [
        'pageId' => function () {
            $model = $this->model();
            return $model instanceof Site ? 'site' : $model->id();
        },
        'aiEnabled' => function () {
            return MetaKit::isAiEnabled();
        },
        'reviewEnabled' => function () {
            return MetaKit::isReviewEnabled();
        }
    ]
];
