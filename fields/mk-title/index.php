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
        'pageId' => function ($pageId = null) {
            return $pageId;
        }
    ],
    'computed' => [
        'validationRanges' => function () {
            $fieldType = $this->fieldType();
            $ranges = option('tearoom1.meta-kit.validation.ranges', []);

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
            $siteMetaTitle = '';
            $titleSeparator = '|';

            if ($siteSeo) {
                $appendSiteName = $siteSeo->appendSiteName()->toBool();
                $siteMetaTitle = $siteSeo->metaTitle()->value();
                $titleSeparator = $siteSeo->titleSeparator()->or('|');
            }

            return [
                'ranges' => $this->validationRanges(),
                'appendSiteName' => $appendSiteName,
                'siteMetaTitle' => $siteMetaTitle,
                'titleSeparator' => $titleSeparator,
                'fieldType' => $this->fieldType(),
                'pageId' => $this->pageId() ?? $this->model()->id()
            ];
        }
    ]
];
