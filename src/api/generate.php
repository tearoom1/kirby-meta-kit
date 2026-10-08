<?php

use TearoomOne\MetaKit;
use TearoomOne\MetaHelper;

return function () {
    $kirby = kirby();
    $data = $kirby->request()->body()->toArray();
    $text = $data['text'] ?? '';
    $language = $data['language'] ?? MetaHelper::currentLanguageCode($kirby);
    $pageId = $data['pageId'] ?? null;
    $fieldType = $data['fieldType'] ?? 'description'; // 'description' or 'ogDescription'

    if (empty($text)) {
        return [
            'status' => 'error',
            'message' => TearoomOne\Texts::get('error.noText')
        ];
    }

    try {
        $metaKit = new MetaKit($kirby);

        // Build context for generation
        $context = ['language' => $language, 'fieldType' => $fieldType];

        // Add template context if page ID is provided
        if ($pageId) {
            $isSite = ($pageId === 'site');
            $page = $isSite ? $kirby->site() : $kirby->page($pageId);

            if ($page && !$isSite) {
                $context['template'] = $page->intendedTemplate()->name();
            }
        }

        $description = $metaKit->generateDescription($text, $context);

        if (empty($description)) {
            return [
                'status' => 'error',
                'message' => TearoomOne\Texts::get('error.emptyDescription')
            ];
        }

        return [
            'status' => 'success',
            'description' => $description
        ];
    } catch (Exception $e) {
        // Log the error
        MetaKit::log('Meta Kit API Error: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
};
