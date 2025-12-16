<?php

use Kirby\Cms\App;

return [
    'props' => [
        'slug' => function ($slug = null) {
            return $slug;
        }
    ],
    'computed' => [
        'validationSettings' => function () {
            $slugValidation = option('tearoom1.meta-kit.validation.slug', []);

            return [
                'depth' => $slugValidation['depth'] ?? [
                    'optimal' => ['min' => 0, 'max' => 2],
                    'warning' => ['min' => 0, 'max' => 3]
                ],
                'words' => $slugValidation['words'] ?? [
                    'optimal' => ['min' => 1, 'max' => 8],
                    'warning' => ['min' => 1, 'max' => 10]
                ],
                'length' => $slugValidation['length'] ?? [
                    'optimal' => ['min' => 1, 'max' => 60],
                    'warning' => ['min' => 1, 'max' => 70]
                ],
                'wordLength' => $slugValidation['wordLength'] ?? [
                    'optimal' => ['min' => 1, 'max' => 15],
                    'warning' => ['min' => 1, 'max' => 20]
                ]
            ];
        },
        'currentSlug' => function () {
            return $this->slug() ?? $this->model()->slug();
        }
    ]
];
