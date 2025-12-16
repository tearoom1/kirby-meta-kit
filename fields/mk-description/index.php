<?php

use Kirby\Cms\App;

return [
    'props' => [
        'value' => function ($value = null) {
            return $value;
        },
        'fieldType' => function ($fieldType = 'meta') {
            return $fieldType;
        },
        'maxlength' => function ($maxlength = null) {
            return $maxlength;
        }
    ],
    'computed' => [
        'validationRanges' => function () {
            $fieldType = $this->fieldType();
            $ranges = option('tearoom1.meta-kit.validation.ranges', []);

            if ($fieldType === 'og' && isset($ranges['ogDescription'])) {
                return $ranges['ogDescription'];
            }

            return $ranges['description'] ?? [
                'optimal' => ['min' => 140, 'max' => 160],
                'warning' => ['min' => 126, 'max' => 176]
            ];
        },
        'validationSettings' => function () {
            return [
                'ranges' => $this->validationRanges(),
                'fieldType' => $this->fieldType()
            ];
        }
    ]
];
