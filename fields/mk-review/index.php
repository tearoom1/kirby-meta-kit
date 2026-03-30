<?php

use Kirby\Cms\Site;
use TearoomOne\MetaKit;

return [
    'extends' => 'info',
    'props' => [
        'label' => function ($label = 'AI SEO Review') {
            return $label;
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
        }
    ]
];
