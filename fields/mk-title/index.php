<?php

use TearoomOne\ConfigHelper;
use TearoomOne\MetaKit;

return [
    'extends' => 'text',
    'props' => [
        'value' => function ($value = null) {
            return $value;
        },
        'fieldType' => function ($fieldType = 'meta') {
            return $fieldType;
        },
        'pageId' => function ($pageId = null) {
            return $pageId;
        }
    ],
    'computed' => [
        'validationRanges' => function () {
            $fieldType = $this->fieldType() === 'og' ? 'ogTitle' : 'title';
            $model = $this->model();
            $template = method_exists($model, 'intendedTemplate') ? $model->intendedTemplate()->name() : null;
            return ConfigHelper::getValidationRanges($fieldType, $template);
        },
        'validationSettings' => function () {
            $model = $this->model();
            $template = method_exists($model, 'intendedTemplate') ? $model->intendedTemplate()->name() : 'site';
            $siteSettings = ConfigHelper::getSiteSettings();

            return [
                'ranges' => $this->validationRanges(),
                'appendSiteName' => $siteSettings['appendSiteName'],
                'appendSiteNameTo' => $siteSettings['appendSiteNameTo'],
                'siteMetaTitle' => $siteSettings['siteMetaTitle'],
                'titleSeparator' => $siteSettings['titleSeparator'],
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
