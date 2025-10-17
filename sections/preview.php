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
                
                // Get SEO data from object field
                $seoData = $page->seo()->toObject();
                
                // Build meta object
                $title = $seoData && $seoData->metatitle()->isNotEmpty() 
                    ? $seoData->metatitle()->value() 
                    : ($page->title()->value() . ' | ' . site()->title()->value());
                
                $description = $seoData && $seoData->metadescription()->isNotEmpty()
                    ? $seoData->metadescription()->value()
                    : 'No description';
                
                $ogTitle = $seoData && $seoData->ogtitle()->isNotEmpty()
                    ? $seoData->ogtitle()->value()
                    : $title;
                
                $ogDescription = $seoData && $seoData->ogdescription()->isNotEmpty()
                    ? $seoData->ogdescription()->value()
                    : $description;
                
                $ogImage = null;
                if ($seoData && $seoData->ogimage()->isNotEmpty()) {
                    $image = $seoData->ogimage()->toFile();
                    $ogImage = $image ? $image->url() : null;
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
