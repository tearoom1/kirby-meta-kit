<?php

use TearoomOne\ConfigHelper;

return [
    'props' => [
        'slug' => function ($slug = null) {
            return $slug;
        }
    ],
    'computed' => [
        'validationSettings' => function () {
            $template = $this->model()->intendedTemplate()->name();

            return [
                ...ConfigHelper::getSlugValidation($template),
                'template' => $template
            ];
        },
        'currentSlug' => function () {
            return $this->slug() ?? $this->model()->slug();
        }
    ]
];
