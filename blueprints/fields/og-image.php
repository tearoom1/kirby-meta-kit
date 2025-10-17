<?php

use Kirby\Cms\App;

return function (App $kirby) {
    return [
        'type' => 'files',
        'multiple' => false,
        'layout' => 'cards',
        'size' => 'small',
        'query' => 'page.images',
        'uploads' => [
            'template' => 'image',
            'accept' => [
                'image/*'
            ]
        ],
        'preview' => [
            'ratio' => '16/9'
        ],
        'width' => '1/2',
        'help' => 'Recommended size: 1200×630px (16:9 ratio)'
    ];
};
