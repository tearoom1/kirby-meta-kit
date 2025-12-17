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

            // Get template-specific ranges if available
            $template = $this->model()->intendedTemplate()->name();
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
            $siteSeo = $site->content()->get('metakitseo')->toBlocks()->first();

            // Get site settings
            $appendSiteName = false;
            $appendSiteNameTo = '';
            $siteMetaTitle = '';
            $titleSeparator = '|';

            if ($siteSeo) {
                $appendSiteName = $siteSeo->appendSiteName()->toBool();
                $appendSiteNameTo = $siteSeo->appendSiteNameTo()->value();
                $siteMetaTitle = $siteSeo->metaTitle()->value();
                $titleSeparator = $siteSeo->titleSeparator()->or('|')->value();
            }

            return [
                'ranges' => $this->validationRanges(),
                'appendSiteName' => $appendSiteName,
                'appendSiteNameTo' => $appendSiteNameTo,
                'siteMetaTitle' => $siteMetaTitle,
                'titleSeparator' => $titleSeparator,
                'fieldType' => $this->fieldType(),
                'pageId' => $this->pageId() ?? $this->model()->id(),
                'template' => $this->model()->intendedTemplate()->name()
            ];
        }
    ]
];
