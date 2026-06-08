<?php

use TearoomOne\ConfigHelper;
use TearoomOne\MetaKit;

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
            $fieldType = $this->fieldType() === 'og' ? 'ogDescription' : 'description';
            $model = $this->model();
            $template = method_exists($model, 'intendedTemplate') ? $model->intendedTemplate()->name() : null;
            return ConfigHelper::getValidationRanges($fieldType, $template);
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
        },
        'aiEnabled' => function () {
            return MetaKit::isAiEnabled();
        }
    ]
];
