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
    public static function getSeoData($field)
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
    public static function seoDataToArray($seoData)
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

        // Add Site as first row
        $site = $kirby->site();
        $siteSeo = self::getSeoData($site->metaKitSeo());
        $siteLegacy = [];
        if ($site->metatitle()->isNotEmpty()) {
            $siteLegacy['metaTitle'] = $site->metatitle()->value();
        }
        if ($site->metadescription()->isNotEmpty()) {
            $siteLegacy['metaDescription'] = $site->metadescription()->value();
        }
        $result[] = [
            'id' => 'site',
            'title' => $site->title()->value(),
            'url' => $site->url(),
            'panelUrl' => $site->panel()->url(),
            'template' => 'site',
            'hasMetaTitle' => $siteSeo && $siteSeo->metaTitle()->isNotEmpty(),
            'hasMetaDescription' => $siteSeo && $siteSeo->metaDescription()->isNotEmpty(),
            'hasOgImage' => $siteSeo && $siteSeo->ogImage()->isNotEmpty(),
            'robots' => $siteSeo && $siteSeo->robots()->isNotEmpty() ? $siteSeo->robots()->value() : 'index, follow',
            'metaTitle' => $siteSeo && $siteSeo->metaTitle()->isNotEmpty()
                ? $siteSeo->metaTitle()->value()
                : null,
            'metaTitleLength' => $siteSeo && $siteSeo->metaTitle()->isNotEmpty()
                ? mb_strlen($siteSeo->metaTitle()->value())
                : 0,
            'metaDescription' => $siteSeo && $siteSeo->metaDescription()->isNotEmpty()
                ? $siteSeo->metaDescription()->value()
                : null,
            'metaDescriptionLength' => $siteSeo && $siteSeo->metaDescription()->isNotEmpty()
                ? mb_strlen($siteSeo->metaDescription()->value())
                : 0,
            'language' => $languageCode,
            'legacy' => !empty($siteLegacy) ? $siteLegacy : null,
        ];

        foreach ($pages as $page) {
            $seoData = self::getSeoData($page->metaKitSeo());
            $legacy = [];
            if ($page->metatitle()->isNotEmpty()) {
                $legacy['metaTitle'] = $page->metatitle()->value();
            }
            if ($page->Metatitle()->isNotEmpty() && empty($legacy['metaTitle'])) {
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

            $result[] = [
                'id' => $page->id(),
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => $page->intendedTemplate()->name(),
                'hasMetaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty(),
                'hasMetaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty(),
                'hasOgImage' => $seoData && $seoData->ogImage()->isNotEmpty(),
                'robots' => $seoData && $seoData->robots()->isNotEmpty() ? $seoData->robots()->value() : 'index, follow',
                'metaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? $seoData->metaTitle()->value()
                    : null,
                'metaTitleLength' => $seoData && $seoData->metaTitle()->isNotEmpty()
                    ? mb_strlen($seoData->metaTitle()->value())
                    : 0,
                'metaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? $seoData->metaDescription()->value()
                    : null,
                'metaDescriptionLength' => $seoData && $seoData->metaDescription()->isNotEmpty()
                    ? mb_strlen($seoData->metaDescription()->value())
                    : 0,
                'language' => $languageCode,
                'legacy' => !empty($legacy) ? $legacy : null,
            ];
        }

        // Get site SEO settings for title preview
        $siteSeo = self::getSeoData($site->metaKitSeo());
        $appendSiteName = $siteSeo && $siteSeo->appendSiteName()->isNotEmpty()
            ? $siteSeo->appendSiteName()->toBool()
            : true;
        $siteMetaTitle = $siteSeo && $siteSeo->metaTitle()->isNotEmpty()
            ? $siteSeo->metaTitle()->value()
            : $site->title()->value();
        $titleSeparator = $siteSeo && $siteSeo->titleSeparator()->isNotEmpty()
            ? $siteSeo->titleSeparator()->value()
            : '|';

        return [
            'language' => $languageCode,
            'languages' => self::getLanguages(),
            'pages' => $result,
            'legacyMigration' => option('tearoom1.meta-kit.legacyMigration', false),
            'aiEnabled' => \TearoomOne\MetaKit::isAiEnabled(),
            'hasValidLicense' => \TearoomOne\MetaKit::hasValidLicense(),
            'siteSettings' => [
                'appendSiteName' => $appendSiteName,
                'siteMetaTitle' => $siteMetaTitle,
                'titleSeparator' => $titleSeparator,
            ]
        ];
    }

    private static function getLanguages(): array
    {
        $kirby = kirby();
        $languages = $kirby->languages();

        if (!$languages || $languages->count() === 0) {
            return [];
        }

        // Build ordered list: default first, then remaining in configured order
        $ordered = [];
        if ($default = $languages->default()) {
            $ordered[] = $default;
            $languages = $languages->not($default);
        }
        foreach ($languages as $lang) {
            $ordered[] = $lang;
        }

        $result = [];
        foreach ($ordered as $lang) {
            $result[] = [
                'code' => $lang->code(),
                'name' => $lang->name(),
                'default' => $lang->isDefault()
            ];
        }

        return $result;
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

    /**
     * Generate selected fields (title and/or description) for specified pages
     */
    public static function generateAllFields(
        bool $generateTitle = false,
        bool $generateDescription = false,
        array $pageIds = []
    ): array {
        $kirby = kirby();

        // Get pages to process
        if (empty($pageIds)) {
            $pages = $kirby->site()->index();
        } else {
            $pages = [];
            foreach ($pageIds as $pageId) {
                if ($pageId === 'site') {
                    $pages[] = $kirby->site();
                } else {
                    $page = $kirby->page($pageId);
                    if ($page) {
                        $pages[] = $page;
                    }
                }
            }
        }

        $generated = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($pages as $page) {
            $seoData = self::getSeoData($page->metaKitSeo());
            $pageSkipped = true;

            // Generate title if requested
            if ($generateTitle) {
                // Skip if already has title
                if (!$seoData || $seoData->metaTitle()->isEmpty()) {
                    $result = self::generateField($page->id(), 'metaTitle', null, true);

                    if ($result['status'] === 'success') {
                        $generated++;
                        $pageSkipped = false;
                    } else {
                        $failed++;
                        $pageSkipped = false;
                    }
                }
            }

            // Generate description if requested
            if ($generateDescription) {
                // Skip if already has description
                if (!$seoData || $seoData->metaDescription()->isEmpty()) {
                    $result = self::generateField($page->id(), 'metaDescription', null, true);

                    if ($result['status'] === 'success') {
                        $generated++;
                        $pageSkipped = false;
                    } else {
                        $failed++;
                        $pageSkipped = false;
                    }
                }
            }

            if ($pageSkipped) {
                $skipped++;
            }
        }

        // Build message
        $fields = [];
        if ($generateTitle) $fields[] = 'titles';
        if ($generateDescription) $fields[] = 'descriptions';
        $fieldText = implode(' and ', $fields);

        return [
            'status' => 'success',
            'message' => "Generated {$generated} {$fieldText}, skipped {$skipped}, failed {$failed}",
            'generated' => $generated,
            'skipped' => $skipped,
            'failed' => $failed
        ];
    }


    public static function applySingleField(string $pageId, string $fieldName, $value): array
    {
        $kirby = kirby();
        $isSite = ($pageId === 'site');
        $page = $isSite ? $kirby->site() : $kirby->page($pageId);

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
                    'id' => $isSite ? 'site-seo-settings' : 'seo-metadata',
                    'isHidden' => false,
                    'type' => $isSite ? 'mk-site-seo' : 'mk-page-seo'
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
        $site = $kirby->site();
        $pages = $site->index();

        // Filter by specific page IDs if provided
        $pageIds = get('pageIds');
        $includeSite = false;

        if ($pageIds && is_array($pageIds)) {
            // Check if 'site' is in the pageIds
            $includeSite = in_array('site', $pageIds);

            $pages = $pages->filter(function ($page) use ($pageIds) {
                return in_array($page->id(), $pageIds);
            });
        } else {
            // Include site by default when no filter is applied
            $includeSite = true;
        }

        $result = [];

        // Add site as first entry if needed
        if ($includeSite) {
            $siteSeo = self::getSeoData($site->metaKitSeo());
            $siteLegacy = [];

            if ($site->metatitle()->isNotEmpty()) {
                $siteLegacy['metaTitle'] = $site->metatitle()->value();
            }
            if ($site->metadescription()->isNotEmpty()) {
                $siteLegacy['metaDescription'] = $site->metadescription()->value();
            }

            $ogImageData = null;
            if ($siteSeo && $siteSeo->ogImage()->isNotEmpty()) {
                $ogFiles = $siteSeo->ogImage()->toFiles();
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
                'id' => 'site',
                'title' => $site->title()->value(),
                'url' => $site->url(),
                'panelUrl' => $site->panel()->url(),
                'template' => 'site',
                'hasMetaTitle' => $siteSeo && $siteSeo->metaTitle()->isNotEmpty(),
                'hasMetaDescription' => $siteSeo && $siteSeo->metaDescription()->isNotEmpty(),
                'hasOgImage' => $siteSeo && $siteSeo->ogImage()->isNotEmpty(),
                'robots' => $siteSeo && $siteSeo->robots()->isNotEmpty() ? $siteSeo->robots()->value() : 'index, follow',
                'metaTitle' => $siteSeo && $siteSeo->metaTitle()->isNotEmpty()
                    ? $siteSeo->metaTitle()->value()
                    : null,
                'metaDescription' => $siteSeo && $siteSeo->metaDescription()->isNotEmpty()
                    ? $siteSeo->metaDescription()->value()
                    : null,
                'ogImage' => $ogImageData,
                'metaTitleLength' => $siteSeo && $siteSeo->metaTitle()->isNotEmpty()
                    ? mb_strlen($siteSeo->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $siteSeo && $siteSeo->metaDescription()->isNotEmpty()
                    ? mb_strlen($siteSeo->metaDescription()->value())
                    : 0,
                'legacy' => !empty($siteLegacy) ? $siteLegacy : null,
            ];
        }

        foreach ($pages as $page) {
            $seoData = self::getSeoData($page->metaKitSeo());
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
                'robots' => $seoData && $seoData->robots()->isNotEmpty() ? $seoData->robots()->value() : 'index, follow',
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
        $isSite = ($pageId === 'site');
        $page = $isSite ? $kirby->site() : $kirby->page($pageId);

        if (!$page) {
            return [
                'status' => 'error',
                'message' => 'Page not found'
            ];
        }

        $seoData = self::getSeoData($page->metaKitSeo());
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
            'id' => $isSite ? 'site' : $page->id(),
            'title' => $page->title()->value(),
            'url' => $page->url(),
            'panelUrl' => $page->panel()->url(),
            'template' => $isSite ? 'site' : $page->intendedTemplate()->name(),
            'hasMetaTitle' => $seoData && $seoData->metaTitle()->isNotEmpty(),
            'hasMetaDescription' => $seoData && $seoData->metaDescription()->isNotEmpty(),
            'hasOgImage' => $seoData && $seoData->ogImage()->isNotEmpty(),
            'robots' => $seoData && $seoData->robots()->isNotEmpty() ? $seoData->robots()->value() : 'index, follow',
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
     * Get page or site object from pageId
     */
    private static function getPageOrSite(string $pageId)
    {
        $kirby = kirby();
        $isSite = ($pageId === 'site');

        if ($isSite) {
            return $kirby->site();
        }

        $page = $kirby->page($pageId);
        if (!$page) {
            return null;
        }

        return $page;
    }

    /**
     * Get content for AI generation
     * For site: uses home page content
     * For pages: uses page content
     */
    private static function getContentForGeneration($page, bool $isSite = false): string
    {
        $kirby = kirby();

        // For site, use home page content
        if ($isSite) {
            $homePage = $kirby->site()->homePage();
            if ($homePage) {
                $content = self::extractPageContent($homePage);
                if (empty(trim($content))) {
                    $content = $homePage->title()->value();
                }
            } else {
                $content = $page->title()->value();
            }
        } else {
            // Extract all page content including structured fields
            $content = self::extractPageContent($page);
            if (empty(trim($content))) {
                $content = $page->title()->value();
            }
        }

        return $content;
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

            // Get raw value
            $rawValue = $value->value();

            // skip files
            if (str_starts_with(trim($rawValue), 'file:')) {
                continue;
            }

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

    public static function generateField(string $pageId, string $fieldName, string $language = null, bool $save = false): array
    {
        $kirby = kirby();
        $isSite = ($pageId === 'site');
        $page = self::getPageOrSite($pageId);

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

            // Get content for generation
            $content = self::getContentForGeneration($page, $isSite);

            if ($fieldName === 'metaTitle') {
                $result = $metaKit->generateTitle($content, ['language' => $languageCode]);
            } else {
                $result = $metaKit->generateDescription($content, ['language' => $languageCode]);
            }

            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to generate content'
                ];
            }

            // Save if requested
            if ($save) {
                $seoData = self::getSeoData($page->metaKitSeo());
                $seoArray = self::seoDataToArray($seoData);
                $seoArray[$fieldName] = $result;

                $seoBlock = [
                    [
                        'content' => $seoArray,
                        'id' => $isSite ? 'site-seo-settings' : 'seo-metadata',
                        'isHidden' => false,
                        'type' => $isSite ? 'mk-site-seo' : 'mk-page-seo'
                    ]
                ];
                $page->update(['metaKitSeo' => $seoBlock], $languageCode);

                return [
                    'status' => 'success',
                    'message' => ucfirst(str_replace('meta', 'Meta ', $fieldName)) . ' generated successfully',
                    'content' => $result
                ];
            }

            return [
                'status' => 'success',
                'content' => $result
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    public static function generateDescription(string $pageId): array
    {
        $result = self::generateField($pageId, 'metaDescription', null, true);

        // For backwards compatibility, add 'description' key
        if ($result['status'] === 'success' && isset($result['content'])) {
            $result['description'] = $result['content'];
        }

        return $result;
    }
}
