<?php

return [
    'props' => [
        'label' => function (string $label = 'SEO Preview') {
            return $label;
        }
    ],
    'computed' => [
        'metaTitle' => function () {
            $page = $this->model();
            return $page->metaTitle()->isNotEmpty() 
                ? $page->metaTitle()->value() 
                : ($page->title()->value() . ' | ' . site()->title()->value());
        },
        'metaDescription' => function () {
            $page = $this->model();
            if ($page->metaDescription()->isNotEmpty()) {
                return $page->metaDescription()->excerpt(160);
            }
            return $page->text()->excerpt(160);
        },
        'ogImage' => function () {
            $page = $this->model();
            $image = $page->ogImage()->toFile() ?? site()->ogImage()->toFile();
            return $image ? $image->url() : null;
        },
        'pageUrl' => function () {
            return $this->model()->url();
        }
    ]
];
