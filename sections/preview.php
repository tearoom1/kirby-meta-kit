<?php

use TearoomOne\MetaHelper;

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
                $seoData = MetaHelper::getSeoData($page->metaKitSeo());

                // Build title and descriptions using helper
                $title = MetaHelper::buildTitle($page, site(), $seoData, 'meta');
                $description = MetaHelper::buildDescription($page, site(), $seoData);

                $ogTitle = MetaHelper::buildTitle($page, site(), $seoData, 'og');
                $ogDescription = MetaHelper::buildOgDescription($page, site(), $seoData, $description);

                $ogImage = null;
                if ($seoData && $seoData->ogImage()->isNotEmpty()) {
                    // Get the first file from the files field
                    $files = $seoData->ogImage()->toFiles();
                    if ($files && $files->count() > 0) {
                        $image = $files->first();
                        // Resize to OG dimensions (1200x630)
                        $ogImage = $image ? $image->crop(1200, 630)->url() : null;
                    }
                }

                // Fallback to site default OG image
                if (!$ogImage) {
                    $siteSeo = MetaHelper::getSeoData(site()->metaKitSeo());
                    if ($siteSeo && $siteSeo->ogImage()->isNotEmpty()) {
                        $siteFiles = $siteSeo->ogImage()->toFiles();
                        if ($siteFiles && $siteFiles->count() > 0) {
                            $siteImage = $siteFiles->first();
                            $ogImage = $siteImage ? $siteImage->crop(1200, 630)->url() : null;
                        }
                    }
                }

                return [
                    'url' => $page->url(),
                    'title' => html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'description' => html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'ogTitle' => html_entity_decode($ogTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'ogDescription' => html_entity_decode($ogDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
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
