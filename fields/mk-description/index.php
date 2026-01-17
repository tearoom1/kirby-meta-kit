<?php

use Kirby\Cms\App;

return [
    'extends' => 'textarea',
    'props' => [
        'value' => function ($value = null) {
            return $value;
        },
        'fieldType' => function ($fieldType = 'meta') {
            return $fieldType;
        },
        'pageId' => function ($pageId = null) {
            return $pageId;
        },
        'maxlength' => function ($maxlength = null) {
            return $maxlength;
        }
    ],
    'computed' => [
        'validationRanges' => function () {
            $fieldType = $this->fieldType();
            $validation = option('tearoom1.meta-kit.validation', []);
            $ranges = $validation['ranges'] ?? [];
            $templates = $validation['templates'] ?? [];

            // Get template-specific ranges if available (only for pages, not site)
            $model = $this->model();
            $template = method_exists($model, 'intendedTemplate') ? $model->intendedTemplate()->name() : 'site';
            $fieldKey = $fieldType === 'og' ? 'ogDescription' : 'description';

            // Check for template-specific ranges
            if (isset($templates[$template][$fieldKey])) {
                return $templates[$template][$fieldKey];
            }

            // Fall back to default ranges
            if ($fieldType === 'og' && isset($ranges['ogDescription'])) {
                return $ranges['ogDescription'];
            }

            return $ranges['description'] ?? [
                'optimal' => ['min' => 140, 'max' => 160],
                'warning' => ['min' => 126, 'max' => 176]
            ];
        },
        'validationSettings' => function () {
            $model = $this->model();
            $template = method_exists($model, 'intendedTemplate') ? $model->intendedTemplate()->name() : 'site';
            return [
                'ranges' => $this->validationRanges(),
                'fieldType' => $this->fieldType(),
                'pageId' => $this->pageId() ?? $model->id(),
                'template' => $template
            ];
        }
    ]
];
