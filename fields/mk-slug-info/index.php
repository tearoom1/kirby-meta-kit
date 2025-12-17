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
            $validation = option('tearoom1.meta-kit.validation', []);
            $slugValidation = $validation['slug'] ?? [];
            $templates = $validation['templates'] ?? [];

            // Get template-specific slug validation if available
            $template = $this->model()->intendedTemplate()->name();

            // Default slug validation ranges
            $defaultDepth = $slugValidation['depth'] ?? [
                'optimal' => ['min' => 0, 'max' => 2],
                'warning' => ['min' => 0, 'max' => 3]
            ];
            $defaultWords = $slugValidation['words'] ?? [
                'optimal' => ['min' => 1, 'max' => 8],
                'warning' => ['min' => 1, 'max' => 10]
            ];
            $defaultLength = $slugValidation['length'] ?? [
                'optimal' => ['min' => 1, 'max' => 60],
                'warning' => ['min' => 1, 'max' => 70]
            ];
            $defaultWordLength = $slugValidation['wordLength'] ?? [
                'optimal' => ['min' => 1, 'max' => 15],
                'warning' => ['min' => 1, 'max' => 20]
            ];

            // Check for template-specific slug validation
            if (isset($templates[$template]['slug'])) {
                $templateSlug = $templates[$template]['slug'];

                return [
                    'depth' => $templateSlug['depth'] ?? $defaultDepth,
                    'words' => $templateSlug['words'] ?? $defaultWords,
                    'length' => $templateSlug['length'] ?? $defaultLength,
                    'wordLength' => $templateSlug['wordLength'] ?? $defaultWordLength,
                    'template' => $template
                ];
            }

            return [
                'depth' => $defaultDepth,
                'words' => $defaultWords,
                'length' => $defaultLength,
                'wordLength' => $defaultWordLength,
                'template' => $template
            ];
        },
        'currentSlug' => function () {
            return $this->slug() ?? $this->model()->slug();
        }
    ]
];
