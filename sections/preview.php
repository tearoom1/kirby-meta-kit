<?php

return [
    'props' => [
        'label' => function (string $label = 'SEO Preview') {
            return $label;
        }
    ],
    'computed' => [
        'meta' => function () {
            $page = $this->model();

            try {
                // Get the changes version if it exists (unsaved changes)
                $changesVersion = $page->version('changes');
                if ($changesVersion->exists('current')) {
                    $page = $page->clone(['content' => $changesVersion->content()->toArray()]);
                }

                // Get SEO data from blocks or object field
                $seoData = \TearoomOne\MetaHelper::getSeoData($page->seo());

                // Build title and descriptions using helper
                $title = \TearoomOne\MetaHelper::buildTitle($page, site(), $seoData);
                $description = \TearoomOne\MetaHelper::buildDescription($page, site(), $seoData);
                
                $ogTitle = $seoData && $seoData->ogtitle()->isNotEmpty()
                    ? $seoData->ogtitle()->value()
                    : $title;

                $ogDescription = \TearoomOne\MetaHelper::buildOgDescription($page, site(), $seoData, $description);

                $ogImage = null;
                if ($seoData && $seoData->ogimage()->isNotEmpty()) {
                    // Get the first file from the files field
                    $files = $seoData->ogimage()->toFiles();
                    if ($files && $files->count() > 0) {
                        $image = $files->first();
                        // Resize to OG dimensions (1200x630)
                        $ogImage = $image ? $image->crop(1200, 630)->url() : null;
                    }
                }

                // Fallback to site default OG image
                if (!$ogImage) {
                    $siteSeo = \TearoomOne\MetaHelper::getSeoData(site()->seo());
                    if ($siteSeo && $siteSeo->ogImage()->isNotEmpty()) {
                        $siteImage = $siteSeo->ogImage()->toFile();
                        $ogImage = $siteImage ? $siteImage->crop(1200, 630)->url() : null;
                    }
                }

                return [
                    'url' => $page->url(),
                    'title' => $title,
                    'description' => $description,
                    'ogTitle' => $ogTitle,
                    'ogDescription' => $ogDescription,
                    'ogImage' => $ogImage
                ];
            } catch (Exception $e) {
                // Return fallback data if error
                return [
                    'url' => $page->url(),
                    'title' => $page->title()->value() . ' | ' . site()->title()->value(),
                    'description' => 'Error loading SEO data: ' . $e->getMessage(),
                    'ogTitle' => $page->title()->value(),
                    'ogDescription' => '',
                    'ogImage' => null
                ];
            }
        }
    ]
];
