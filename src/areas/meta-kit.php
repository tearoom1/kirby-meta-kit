<?php

use TearoomOne\MetaKitController;

return [
    'label' => 'Meta Kit',
    'icon' => 'seo',
    'menu' => true,
    'link' => 'meta-kit',
    'views' => [
        [
            'pattern' => 'meta-kit',
            'action' => function () {
                return [
                    'component' => 'meta-kit-view',
                    'title' => 'Meta Kit',
                    'props' => [
                        'pages' => MetaKitController::getPages()
                    ]
                ];
            }
        ]
    ]
];
