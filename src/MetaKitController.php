<?php

namespace TearoomOne;

class MetaKitController
{
    /**
     * Avoid short text, numbers and file strings
     */
    const int MIN_TEXT_LENGTH = 25;

    /**
     * Get SEO data from blocks or object field
     */
    private static function getSeoData($field)
    {
        if (!$field || $field->isEmpty()) {
            return null;
        }

        // Try blocks format first
        $blocks = $field->toBlocks();
        if ($blocks && $blocks->count() > 0) {
            return $blocks->first()->content();
        }

        // Fallback to object format (legacy)
        return $field->toObject();
    }

    /**
     * Convert SEO data to array for saving
     */
    private static function seoDataToArray($seoData)
    {
        if (!$seoData) {
            return [];
        }

        // If it's already an array, return it
        if (is_array($seoData)) {
            return $seoData;
        }

        // Convert object to array
        if (method_exists($seoData, 'toArray')) {
            return $seoData->toArray();
        }

        return [];
    }

    public static function getPages(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $result = [];

        // Get current language
        $language = $kirby->language();
        $languageCode = $language ? $language->code() : null;

        foreach ($pages as $page) {
            $seoData = self::getSeoData($page->metaKitSeo());

            $result[] = [
                'id' => $page->id(),
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => $page->intendedTemplate()->name(),
                'hasMetaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty(),
                'hasMetaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty(),
                'hasOgImage' => $seoData && $seoData->ogImage()->isNotEmpty(),
                'noIndex' => $seoData && $seoData->noIndex()->toBool(),
                'metaTitleLength' => $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? mb_strlen($seoData->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? mb_strlen($seoData->metaDescription()->value())
                    : 0,
                'language' => $languageCode,
            ];
        }

        return [
            'language' => $languageCode,
            'languages' => self::getLanguages(),
            'pages' => $result
        ];
    }

    private static function getLanguages(): array
    {
        $kirby = kirby();
        $languages = $kirby->languages();

        if (!$languages || $languages->count() === 0) {
            return [];
        }

        $result = [];
        foreach ($languages as $lang) {
            $result[] = [
                'code' => $lang->code(),
                'name' => $lang->name(),
                'default' => $lang->isDefault()
            ];
        }

        return $result;
    }

    public static function generateDescription(string $pageId): array
    {
        $kirby = kirby();
        $page = $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        try {
            $metaKit = new MetaKit($kirby);
            $languageCode = $kirby->language()?->code() ?? 'en';

            // Get page content for generation
            $content = $page->text()->or($page->title())->value();
            $description = $metaKit->generateDescription($content, ['language' => $languageCode]);

            if ($description) {
                $seoData = self::getSeoData($page->metaKitSeo());
                $seoArray = self::seoDataToArray($seoData);
                $seoArray['metaDescription'] = $description;

                $seoBlock = [
                    [
                        'content' => $seoArray,
                        'id' => 'seo-metadata',
                        'isHidden' => false,
                        'type' => 'seo'
                    ]
                ];
                $page->update(['metaKitSeo' => $seoBlock], $languageCode);

                return [
                    'status' => 'success',
                    'message' => 'Description generated successfully',
                    'description' => $description
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to generate description'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function convertAllLegacyFields(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $converted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($pages as $page) {
            try {
                $result = self::convertLegacyMetadata($page->id());
                if ($result['status'] === 'success') {
                    $converted++;
                } elseif ($result['status'] === 'info') {
                    $skipped++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return [
            'status' => 'success',
            'message' => "Migrated {$converted} pages, skipped {$skipped}, errors {$errors}",
            'converted' => $converted,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    public static function convertAllToBlocks(): array
    {
        $kirby = kirby();
        $converted = 0;
        $skipped = 0;
        $errors = 0;

        try {
            $site = $kirby->site();
            $updates = [];
            $needsUpdate = false;

            $seoField = $site->metaKitSeo();
            if ($seoField->isNotEmpty()) {
                $rawValue = $seoField->value();
                $isBlocks = is_string($rawValue) && str_starts_with(trim($rawValue), '[') && json_decode(trim($rawValue), true) !== null;
                if (!$isBlocks) {
                    $obj = $seoField->toObject();
                    if ($obj) {
                        $updates['metaKitSeo'] = [
                            [
                                'content' => [
                                    'appendSiteName' => $obj->appendsitename()->toBool(),
                                    'titleSeparator' => $obj->titleseparator()->or('|')->value(),
                                    'metaTitle' => $obj->metatitle()->value(),
                                    'metaDescription' => $obj->metadescription()->value(),
                                    'metaKeywords' => $obj->metakeywords()->value(),
                                    'ogImage' => $obj->ogimage()->isNotEmpty() ? $obj->ogimage()->toFiles()->pluck('uuid')->toArray() : []
                                ],
                                'id' => 'seo-settings',
                                'isHidden' => false,
                                'type' => 'seo-settings'
                            ]
                        ];
                        $needsUpdate = true;
                    }
                }
            }

            $openrouterField = $site->openrouter();
            if ($openrouterField->isNotEmpty()) {
                $rawValue = $openrouterField->value();
                $isBlocks = is_string($rawValue) && str_starts_with(trim($rawValue), '[') && json_decode(trim($rawValue), true) !== null;
                if (!$isBlocks) {
                    $obj = $openrouterField->toObject();
                    if ($obj) {
                        $updates['openrouter'] = [
                            [
                                'content' => [
                                    'apiKey' => $obj->apikey()->value(),
                                    'model' => $obj->model()->or('meta-llama/llama-3.2-3b-instruct:free')->value(),
                                    'temperature' => $obj->temperature()->or('0.7')->value()
                                ],
                                'id' => 'openrouter-settings',
                                'isHidden' => false,
                                'type' => 'openrouter'
                            ]
                        ];
                        $needsUpdate = true;
                    }
                }
            }

            $sitemapField = $site->sitemap();
            if ($sitemapField->isNotEmpty()) {
                $rawValue = $sitemapField->value();
                $isBlocks = is_string($rawValue) && str_starts_with(trim($rawValue), '[') && json_decode(trim($rawValue), true) !== null;
                if (!$isBlocks) {
                    $obj = $sitemapField->toObject();
                    if ($obj) {
                        $excludePages = [];
                        if ($obj->exclude()->isNotEmpty()) {
                            $excludePages = $obj->exclude()->toPages()->pluck('uuid')->toArray();
                        }
                        $updates['sitemap'] = [
                            [
                                'content' => [
                                    'exclude' => $excludePages,
                                    'priorityHome' => $obj->priorityhome()->or('1.0')->value(),
                                    'priorityDefault' => $obj->prioritydefault()->or('0.8')->value()
                                ],
                                'id' => 'sitemap-settings',
                                'isHidden' => false,
                                'type' => 'sitemap'
                            ]
                        ];
                        $needsUpdate = true;
                    }
                }
            }

            if ($needsUpdate) {
                $kirby->impersonate('kirby');
                $site->update($updates);
                $converted++;
            } else {
                $skipped++;
            }
        } catch (\Exception $e) {
            $errors++;
        }

        $pages = $kirby->site()->index();
        foreach ($pages as $page) {
            $seoField = $page->metaKitSeo();
            if ($seoField->isEmpty()) {
                continue;
            }
            $rawValue = $seoField->value();
            $isBlocks = false;
            if (is_string($rawValue)) {
                $trimmed = trim($rawValue);
                if (str_starts_with($trimmed, '[')) {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $isBlocks = true;
                    }
                }
            }
            if ($isBlocks) {
                $skipped++;
                continue;
            }
            $obj = $seoField->toObject();
            if (!$obj) {
                $skipped++;
                continue;
            }
            try {
                $ogImageUuids = [];
                if ($obj->ogimage()->isNotEmpty()) {
                    $files = $obj->ogimage()->toFiles();
                    if ($files->count() > 0) {
                        $ogImageUuids = $files->pluck('uuid')->toArray();
                    }
                }
                $seoBlock = [
                    [
                        'content' => [
                            'metaTitle' => $obj->metatitle()->value(),
                            'metaDescription' => $obj->metadescription()->value(),
                            'metaKeywords' => $obj->metakeywords()->value(),
                            'canonicalUrl' => $obj->canonicalurl()->value(),
                            'noIndex' => $obj->noindex()->toBool(),
                            'ogTitle' => $obj->ogtitle()->value(),
                            'ogDescription' => $obj->ogdescription()->value(),
                            'ogImage' => $ogImageUuids
                        ],
                        'id' => 'seo-metadata',
                        'isHidden' => false,
                        'type' => 'seo'
                    ]
                ];
                $kirby->impersonate('kirby');
                $page->update(['metaKitSeo' => $seoBlock], $kirby->language()?->code());
                $converted++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return [
            'status' => 'success',
            'message' => "Converted {$converted}, skipped {$skipped}, errors {$errors}",
            'converted' => $converted,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    public static function generateAllDescriptions(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $generated = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($pages as $page) {
            $seoData = self::getSeoData($page->metaKitSeo());

            // Skip if already has description
            if ($seoData && $seoData->metaDescription()->isNotEmpty()) {
                $skipped++;
                continue;
            }

            $result = self::generateDescription($page->id());

            if ($result['status'] === 'success') {
                $generated++;
            } else {
                $failed++;
            }
        }

        return [
            'status' => 'success',
            'message' => "Generated {$generated} descriptions, skipped {$skipped}, failed {$failed}",
            'generated' => $generated,
            'skipped' => $skipped,
            'failed' => $failed
        ];
    }

    public static function detectLegacyMetadata(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $found = [];

        foreach ($pages as $page) {
            $legacy = [];
            $seoData = self::getSeoData($page->metaKitSeo());
            $current = [];

            // Check for common legacy SEO field names
            // Meta Title variations
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->metatitle()->value();
                $current['metaTitle'] = $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? $seoData->metaTitle()->value()
                    : null;
            } elseif ($page->meta_title()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->meta_title()->value();
                $current['metaTitle'] = $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? $seoData->metaTitle()->value()
                    : null;
            }

            // Meta Description variations
            if ($page->metadescription()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->metadescription()->value();
                $current['metaDescription'] = $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? $seoData->metaDescription()->value()
                    : null;
            } elseif ($page->meta_description()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->meta_description()->value();
                $current['metaDescription'] = $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? $seoData->metaDescription()->value()
                    : null;
            }

            // OG Image
            if ($page->ogimage()->isNotEmpty()) {
                $files = $page->ogimage()->toFiles();
                if ($files->count() > 0) {
                    $legacy['ogImage'] = $files->first()->filename();
                    $current['ogImage'] = $seoData && $seoData->ogImage()->isNotEmpty()
                        ? $seoData->ogImage()->toFiles()->first()?->filename()
                        : null;
                }
            }

            if (!empty($legacy)) {
                $found[] = [
                    'id' => $page->id(),
                    'title' => $page->title()->value(),
                    'fields' => $legacy,
                    'current' => $current
                ];
            }
        }

        return [
            'status' => 'success',
            'found' => count($found),
            'pages' => $found
        ];
    }

    public static function convertLegacyMetadata(string $pageId): array
    {
        $kirby = kirby();
        $page = $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        try {
            $seoData = self::getSeoData($page->metaKitSeo());
            $seoArray = self::seoDataToArray($seoData);
            $converted = [];

            // Migrate legacy fields - Meta Title variations
            if ($page->metatitle()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->metatitle()->value();
                $converted[] = 'metaTitle (from metatitle)';
            } elseif ($page->meta_title()->isNotEmpty() && empty($seoArray['metaTitle'])) {
                $seoArray['metaTitle'] = $page->meta_title()->value();
                $converted[] = 'metaTitle (from Meta-title)';
            }

            // Migrate legacy fields - Meta Description variations
            if ($page->metadescription()->isNotEmpty() && empty($seoArray['metaDescription'])) {
                $seoArray['metaDescription'] = $page->metadescription()->value();
                $converted[] = 'metaDescription (from metadescription)';
            } elseif ($page->meta_description()->isNotEmpty() && empty($seoArray['metaDescription'])) {
                $seoArray['metaDescription'] = $page->meta_description()->value();
                $converted[] = 'metaDescription (from Meta-description)';
            }

            // Migrate legacy fields - OG Image
            if ($page->ogimage()->isNotEmpty() && empty($seoArray['ogImage'])) {
                $seoArray['ogImage'] = $page->ogimage()->toFiles();
                $converted[] = 'ogImage';
            }

            if (!empty($converted)) {
                $languageCode = $kirby->language()?->code();
                $seoBlock = [
                    [
                        'content' => $seoArray,
                        'id' => 'seo-metadata',
                        'isHidden' => false,
                        'type' => 'seo'
                    ]
                ];
                $page->update(['metaKitSeo' => $seoBlock], $languageCode);

                return [
                    'status' => 'success',
                    'message' => 'Converted: ' . implode(', ', $converted),
                    'converted' => $converted
                ];
            } else {
                return [
                    'status' => 'info',
                    'message' => 'No legacy fields found or already converted'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function applySingleField(string $pageId, string $fieldName, $value): array
    {
        $kirby = kirby();
        $page = $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        try {
            $seoData = self::getSeoData($page->metaKitSeo());
            $seoArray = self::seoDataToArray($seoData);

            // Handle ogImage specially - it needs to be an array of UUIDs
            if ($fieldName === 'ogImage') {
                if (empty($value)) {
                    $seoArray[$fieldName] = [];
                } else {
                    // Value can be a UUID string or filename
                    // Try to find the file
                    $file = null;
                    if (strpos($value, 'file://') === 0) {
                        // Already a UUID
                        $file = $kirby->file(str_replace('file://', '', $value));
                    } else {
                        // Try as filename from the page
                        $file = $page->file($value);
                    }

                    if ($file) {
                        $seoArray[$fieldName] = [$file->uuid()->toString()];
                    } else {
                        return [
                            'status' => 'error',
                            'message' => 'Image file not found'
                        ];
                    }
                }
            } else {
                // Update the specific field normally
                $seoArray[$fieldName] = $value;

                // Also update OG description when updating meta description
                if ($fieldName === 'metaDescription') {
                    $seoArray['ogDescription'] = $value;
                }

                // Also update OG title when updating meta title
                if ($fieldName === 'metaTitle') {
                    $seoArray['ogTitle'] = $value;
                }
            }

            $languageCode = $kirby->language()?->code();
            $seoBlock = [
                [
                    'content' => $seoArray,
                    'id' => 'seo-metadata',
                    'isHidden' => false,
                    'type' => 'seo'
                ]
            ];
            $page->update(['metaKitSeo' => $seoBlock], $languageCode);

            return [
                'status' => 'success',
                'message' => 'Field updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public static function getPagesWithContent(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();

        // Filter by specific page IDs if provided
        $pageIds = get('pageIds');
        if ($pageIds && is_array($pageIds)) {
            $pages = $pages->filter(function ($page) use ($pageIds) {
                return in_array($page->id(), $pageIds);
            });
        }

        $result = [];

        foreach ($pages as $page) {
            $seoData = self::getSeoData($page->seo());
            $legacy = [];

            // Check for legacy fields
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->metatitle()->value();
            }
            if ($page->Metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->Metatitle()->value();
            }
            if ($page->customtitle()->isNotEmpty() && empty($legacy['metaTitle'])) {
                $legacy['metaTitle'] = $page->customtitle()->value();
            }
            if ($page->seotitle()->isNotEmpty() && empty($legacy['metaTitle'])) {
                $legacy['metaTitle'] = $page->seotitle()->value();
            }

            if ($page->metadescription()->isNotEmpty()) {
                $legacy['metaDescription'] = $page->metadescription()->value();
            }
            if ($page->seodescription()->isNotEmpty() && empty($legacy['metaDescription'])) {
                $legacy['metaDescription'] = $page->seodescription()->value();
            }

            $ogImageData = null;
            if ($seoData && $seoData->ogImage()->isNotEmpty()) {
                $ogFiles = $seoData->ogImage()->toFiles();
                if ($ogFiles->count() > 0) {
                    $ogFile = $ogFiles->first();
                    $ogImageData = [
                        'filename' => $ogFile->filename(),
                        'url' => $ogFile->url(),
                        'uuid' => $ogFile->uuid()?->toString()
                    ];
                }
            }

            $result[] = [
                'id' => $page->id(),
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => $page->intendedTemplate()->name(),
                'hasMetaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty(),
                'hasMetaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty(),
                'hasOgImage' => $seoData && $seoData->ogImage()->isNotEmpty(),
                'noIndex' => $seoData && $seoData->noIndex()->toBool(),
                'metaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? $seoData->metaTitle()->value()
                    : null,
                'metaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? $seoData->metaDescription()->value()
                    : null,
                'ogImage' => $ogImageData,
                'metaTitleLength' => $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? mb_strlen($seoData->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? mb_strlen($seoData->metaDescription()->value())
                    : 0,
                'legacy' => !empty($legacy) ? $legacy : null,
            ];
        }

        return [
            'status' => 'success',
            'data' => $result
        ];
    }

    public static function getSinglePage(string $pageId): array
    {
        $kirby = kirby();
        $page = $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        $seoData = self::getSeoData($page->seo());
        $legacy = [];

        // Check for legacy fields
        if ($page->metatitle()->isNotEmpty()) {
            $legacy['metaTitle'] = $page->metatitle()->value();
        }
        if ($page->Metatitle()->isNotEmpty()) {
            $legacy['metaTitle'] = $page->Metatitle()->value();
        }
        if ($page->customtitle()->isNotEmpty() && empty($legacy['metaTitle'])) {
            $legacy['metaTitle'] = $page->customtitle()->value();
        }
        if ($page->seotitle()->isNotEmpty() && empty($legacy['metaTitle'])) {
            $legacy['metaTitle'] = $page->seotitle()->value();
        }

        if ($page->metadescription()->isNotEmpty()) {
            $legacy['metaDescription'] = $page->metadescription()->value();
        }
        if ($page->seodescription()->isNotEmpty() && empty($legacy['metaDescription'])) {
            $legacy['metaDescription'] = $page->seodescription()->value();
        }

        $ogImageData = null;
        if ($seoData && $seoData->ogImage()->isNotEmpty()) {
            $ogFiles = $seoData->ogImage()->toFiles();
            if ($ogFiles->count() > 0) {
                $ogFile = $ogFiles->first();
                $ogImageData = [
                    'filename' => $ogFile->filename(),
                    'url' => $ogFile->url(),
                    'uuid' => $ogFile->uuid()?->toString()
                ];
            }
        }

        $result = [
            'id' => $page->id(),
            'title' => $page->title()->value(),
            'url' => $page->url(),
            'panelUrl' => $page->panel()->url(),
            'template' => $page->intendedTemplate()->name(),
            'hasMetaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty(),
            'hasMetaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty(),
            'hasOgImage' => $seoData && $seoData->ogImage()->isNotEmpty(),
            'noIndex' => $seoData && $seoData->noIndex()->toBool(),
            'metaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty()
                ? $seoData->metaTitle()->value()
                : null,
            'metaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty()
                ? $seoData->metaDescription()->value()
                : null,
            'ogImage' => $ogImageData,
            'metaTitleLength' => $seoData && $seoData->metaTitle()->isNotEmpty()
                ? mb_strlen($seoData->metaTitle()->value())
                : 0,
            'metaDescriptionLength' => $seoData && $seoData->metaDescription()->isNotEmpty()
                ? mb_strlen($seoData->metaDescription()->value())
                : 0,
            'legacy' => !empty($legacy) ? $legacy : null,
        ];

        return [
            'status' => 'success',
            'data' => $result
        ];
    }

    /**
     * Extract all text content from a page
     */
    private static function extractPageContent($page): string
    {
        $texts = [];

        // Add title
        $texts[] = $page->title()->value();

        // Extract from all fields
        foreach ($page->content()->fields() as $key => $field) {
            if (in_array($key, ['title', 'slug', 'template', 'seo', 'ogimage'])) {
                continue;
            }

            $value = $page->content()->get($key);
            if ($value->isEmpty()) {
                continue;
            }

            if ($value->toFiles()->isNotEmpty()) {
                continue;
            }

            // Get raw value
            $rawValue = $value->value();

            // Check if it's JSON (blocks, layout, structure)
            if (is_string($rawValue) && (str_starts_with(trim($rawValue), '[') || str_starts_with(trim($rawValue), '{'))) {
                $decoded = json_decode($rawValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Extract text from structured data
                    $extracted = self::extractTextFromStructure($decoded);
                    if (!empty($extracted)) {
                        $texts[] = $extracted;
                    }
                    continue;
                }
            }

            // Regular text field
            $text = strip_tags($rawValue);
            if (strlen(trim($text)) > self::MIN_TEXT_LENGTH) {
                $texts[] = $text;
            }

        }
        return implode("\n\n", array_filter($texts));
    }

    /**
     * Recursively extract only text content from structured data
     */
    private static function extractTextFromStructure($data): string
    {
        if (!is_array($data)) {
            return is_string($data) ? strip_tags($data) : '';
        }

        $texts = [];

        foreach ($data as $key => $value) {
            // Skip metadata keys
            if (in_array($key, ['id', 'type', 'isHidden', 'attrs'])) {
                continue;
            }

            if (is_array($value)) {
                // Recursively extract from nested structures
                $extracted = self::extractTextFromStructure($value);
                if (!empty(trim($extracted))) {
                    $texts[] = $extracted;
                }
            } elseif (is_string($value) && strlen(trim(strip_tags($value))) > self::MIN_TEXT_LENGTH) {
                // Only include actual text content
                $texts[] = strip_tags($value);
            }
        }

        return implode(' ', $texts);
    }

    function extractTextFromContent($content)
    {
        $texts = [];

        foreach ($content->data() as $key => $field) {
            // If it's a basic text-like field
            if (in_array($field->type(), ['text', 'textarea', 'writer'])) {
                $texts[] = $field->value();
            }

            // Structure fields → loop entries
            if ($field->type() === 'structure') {
                foreach ($field->toStructure() as $entry) {
                    $texts = array_merge($texts, extractTextFromContent($entry));
                }
            }

            // Layout fields → columns → blocks
            if ($field->type() === 'layout') {
                foreach ($field->toLayouts() as $layout) {
                    foreach ($layout->columns() as $column) {
                        foreach ($column->blocks() as $block) {
                            $texts = array_merge($texts, extractTextFromBlock($block));
                        }
                    }
                }
            }

            // Blocks field
            if ($field->type() === 'blocks') {
                foreach ($field->toBlocks() as $block) {
                    $texts = array_merge($texts, extractTextFromBlock($block));
                }
            }
        }

        return $texts;
    }

    function extractTextFromBlock($block)
    {
        $texts = [];
        foreach ($block->content()->data() as $key => $subfield) {
            if (in_array($subfield->type(), ['text', 'textarea', 'writer'])) {
                $texts[] = $subfield->value();
            }
            // Recursively handle nested structures/layouts/blocks inside a block
            if (in_array($subfield->type(), ['structure', 'layout', 'blocks'])) {
                $texts = array_merge($texts, extractTextFromContent($block->content()));
            }
        }
        return $texts;
    }

    public static function generateField(string $pageId, string $fieldName, string $language = null): array
    {
        $kirby = kirby();
        $page = $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        try {
            $metaKit = new MetaKit($kirby);

            // Use provided language or fall back to current language
            $languageCode = $language ?: $kirby->language()?->code();

            // Set the language context if provided
            if ($language && $kirby->multilang()) {
                $kirby->setCurrentLanguage($language);
            }

            // Extract all page content including structured fields
            $content = self::extractPageContent($page);

            // Fallback to title if no content found
            if (empty(trim($content))) {
                $content = $page->title()->value();
            }

            if ($fieldName === 'metaTitle') {
                $result = $metaKit->generateTitle($content, ['language' => $languageCode]);
            } else {
                $result = $metaKit->generateDescription($content, ['language' => $languageCode]);
            }

            if ($result) {
                return [
                    'status' => 'success',
                    'content' => $result
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to generate content'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
