<?php

use TearoomOne\MetaKitController;

return [
    'label' => 'Meta Kit',
    'icon' => 'wand',
    'menu' => fn () => MetaKitController::canAccess(),
    'link' => 'meta-kit',
    'views' => [
        [
            'pattern' => 'meta-kit',
            'action' => function () {
                if (!MetaKitController::canAccess()) {
                    throw new \Kirby\Exception\PermissionException('You are not allowed to access Meta Kit');
                }

                $kirby = kirby();

                // Get language from query parameter
                $languageCode = get('language');
                if ($languageCode && $kirby->multilang()) {
                    $kirby->setCurrentLanguage($languageCode);
                }

                $data = MetaKitController::getPages();

                return [
                    'component' => 'meta-kit-view',
                    'title' => 'Meta Kit',
                    'props' => [
                        'pages' => $data['pages'],
                        'language' => $data['language'],
                        'languages' => $data['languages'],
                        'aiEnabled' => $data['aiEnabled'],
                        'reviewEnabled' => $data['reviewEnabled'],
                        'siteSettings' => $data['siteSettings'],
                        'validationSettings' => $data['validationSettings'] ?? []
                    ]
                ];
            }
        ]
    ]
];
