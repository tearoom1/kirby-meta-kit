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
            $site = site();

            try {
                // Get the changes version if it exists (unsaved changes)
                $changesVersion = $page->version('changes');
                if ($changesVersion->exists(kirby()->language()?->code() ?? 'current')) {
                    $page = $page->clone([
                        'content' => $changesVersion->content(kirby()->language()?->code())->toArray()
                    ]);
                }

                // Build title and descriptions using helper (reads from flat page fields)
                $title = MetaHelper::buildTitle($page, $site, 'meta');
                $description = MetaHelper::buildDescription($page, $site);

                $ogTitle = MetaHelper::buildTitle($page, $site, 'og');
                $ogDescription = MetaHelper::buildOgDescription($page, $site, $description);

                // Get OG image from flat page field
                $ogImage = null;
                if ($page->ogImage()->isNotEmpty()) {
                    $files = $page->ogImage()->toFiles();
                    if ($files && $files->count() > 0) {
                        $image = $files->first();
                        $ogImage = $image ? $image->crop(1200, 630)->url() : null;
                    }
                }

                // Fallback to site default OG image (flat field)
                if (!$ogImage && $site->ogImage()->isNotEmpty()) {
                    $siteFiles = $site->ogImage()->toFiles();
                    if ($siteFiles && $siteFiles->count() > 0) {
                        $siteImage = $siteFiles->first();
                        $ogImage = $siteImage ? $siteImage->crop(1200, 630)->url() : null;
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
                    'title' => $page->title()->value() . ' | ' . $site->title()->value(),
                    'description' => 'Error loading SEO data: ' . $e->getMessage(),
                    'ogTitle' => $page->title()->value(),
                    'ogDescription' => '',
                    'ogImage' => null
                ];
            }
        }
    ]
];
