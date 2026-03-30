<?php

use TearoomOne\MetaKitController;

return [
    'label' => 'Meta Kit',
    'icon' => 'wand',
    'menu' => true,
    'link' => 'meta-kit',
    'views' => [
        [
            'pattern' => 'meta-kit',
            'action' => function () {
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
                        'hasValidLicense' => $data['hasValidLicense'],
                        'siteSettings' => $data['siteSettings'],
                        'validationSettings' => $data['validationSettings'] ?? []
                    ]
                ];
            }
        ]
    ]
];
