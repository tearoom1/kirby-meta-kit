<?php

use TearoomOne\MetaKitController;

// $variant: 'v1' (current design) or 'v2' (temporary design comparison)
return fn (string $variant = 'v1') => [
    'label' => $variant === 'v2' ? 'Meta Kit (Neu)' : 'Meta Kit',
    'icon' => 'wand',
    'menu' => fn () => MetaKitController::canAccess(),
    'link' => $variant === 'v2' ? 'meta-kit-v2' : 'meta-kit',
    'views' => [
        [
            'pattern' => $variant === 'v2' ? 'meta-kit-v2' : 'meta-kit',
            'action' => function () use ($variant) {
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
                        'validationSettings' => $data['validationSettings'] ?? [],
                        'variant' => $variant
                    ]
                ];
            }
        ]
    ]
];
