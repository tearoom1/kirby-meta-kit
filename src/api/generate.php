<?php

use TearoomOne\MetaKit;

return function () {
    $kirby = kirby();
    $data = $kirby->request()->body()->toArray();
    $text = $data['text'] ?? '';
    $language = $data['language'] ?? ($kirby->language()?->code() ?? 'en');
    $pageId = $data['pageId'] ?? null;
    $fieldType = $data['fieldType'] ?? 'description'; // 'description' or 'ogDescription'

    if (empty($text)) {
        return [
            'status' => 'error',
            'message' => 'No text provided'
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
                'message' => 'AI returned empty description. Check your API key and logs.'
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
