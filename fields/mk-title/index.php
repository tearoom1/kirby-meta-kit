<?php

use Kirby\Cms\App;

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
            $fieldType = $this->fieldType();
            $validation = option('tearoom1.meta-kit.validation', []);
            $ranges = $validation['ranges'] ?? [];
            $templates = $validation['templates'] ?? [];

            // Get template-specific ranges if available (only for pages, not site)
            $model = $this->model();
            $template = method_exists($model, 'intendedTemplate') ? $model->intendedTemplate()->name() : 'site';
            $fieldKey = $fieldType === 'og' ? 'ogTitle' : 'title';

            // Check for template-specific ranges
            if (isset($templates[$template][$fieldKey])) {
                return $templates[$template][$fieldKey];
            }

            // Fall back to default ranges
            if ($fieldType === 'og' && isset($ranges['ogTitle'])) {
                return $ranges['ogTitle'];
            }

            return $ranges['title'] ?? [
                'optimal' => ['min' => 20, 'max' => 60],
                'warning' => ['min' => 15, 'max' => 75]
            ];
        },
        'validationSettings' => function () {
            $site = kirby()->site();
            $model = $this->model();
            $template = method_exists($model, 'intendedTemplate') ? $model->intendedTemplate()->name() : 'site';

            // Get site settings from flat fields
            $appendSiteName = $site->appendSiteName()->isNotEmpty()
                ? $site->appendSiteName()->toBool()
                : false;
            $appendSiteNameTo = $site->appendSiteNameTo()->isNotEmpty()
                ? $site->appendSiteNameTo()->value()
                : '';
            $siteMetaTitle = $site->metaTitle()->isNotEmpty()
                ? $site->metaTitle()->value()
                : '';
            $titleSeparator = $site->titleSeparator()->isNotEmpty()
                ? $site->titleSeparator()->value()
                : '|';

            return [
                'ranges' => $this->validationRanges(),
                'appendSiteName' => $appendSiteName,
                'appendSiteNameTo' => $appendSiteNameTo,
                'siteMetaTitle' => $siteMetaTitle,
                'titleSeparator' => $titleSeparator,
                'fieldType' => $this->fieldType(),
                'pageId' => $this->pageId() ?? $model->id(),
                'template' => $template
            ];
        }
    ]
];
