<?php

use TearoomOne\MetaKit;

return function () {
    $kirby = kirby();
    $data = $kirby->request()->body()->toArray();
    $text = $data['text'] ?? '';
    $language = $data['language'] ?? ($kirby->language()?->code() ?? 'en');

    if (empty($text)) {
        return [
            'status' => 'error',
            'message' => 'No text provided'
        ];
    }

    try {
        $metaKit = new MetaKit($kirby);
        $description = $metaKit->generateDescription($text, ['language' => $language]);

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
        kirbylog('Meta Kit API Error: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
};
