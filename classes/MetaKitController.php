<?php

namespace TearoomOne;

class MetaKitController
{
    /**
     * Avoid short text, numbers and file strings
     */
    const int MIN_TEXT_LENGTH = 25;

    /**
     * Check whether the current user may access the plugin
     * (view SEO data, run generation/review). Admins always pass.
     * Additional roles can be granted via the `allowedRoles` option.
     */
    public static function canAccess(): bool
    {
        $user = kirby()->user();
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        $allowed = option('tearoom1.meta-kit.allowedRoles', []);
        if (!is_array($allowed) || empty($allowed)) {
            return false;
        }
        return in_array($user->role()->name(), $allowed, true);
    }

    private static function canUpdateModel($model): bool
    {
        if (!$model || !method_exists($model, 'permissions')) {
            return false;
        }

        return $model->permissions()->can('update') === true;
    }

    public static function getPages(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index(true);
        $result = [];

        $excludeTemplates = option('tearoom1.meta-kit.excludeTemplates', []);
        $excludeStatus = option('tearoom1.meta-kit.excludeStatus', []);
        if (!is_array($excludeTemplates)) {
            $excludeTemplates = [$excludeTemplates];
        }
        if (!is_array($excludeStatus)) {
            $excludeStatus = [$excludeStatus];
        }

        $languageCode = $kirby->language()?->code();

        // Add Site as first row
        $result[] = PageDataBuilder::fromModel($kirby->site());

        // Add pages
        foreach ($pages as $page) {
            $template = $page->intendedTemplate()->name();
            $status = $page->status();
            if (in_array($template, $excludeTemplates, true) || in_array($status, $excludeStatus, true)) {
                continue;
            }

            $result[] = PageDataBuilder::fromModel($page);
        }

        return [
            'language' => $languageCode,
            'languages' => self::getLanguages(),
            'pages' => $result,
            'aiEnabled' => \TearoomOne\MetaKit::isAiEnabled(),
            'reviewEnabled' => \TearoomOne\MetaKit::isReviewEnabled(),
            'validationSettings' => option('tearoom1.meta-kit.validation', []),
            'siteSettings' => self::getSiteSettings()
        ];
    }

    /**
     * Get site SEO settings for title preview
     */
    private static function getSiteSettings(): array
    {
        return ConfigHelper::getSiteSettings();
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


    /**
     * @deprecated Use generateAllFields() instead, which supports all four SEO field types.
     */
    public static function generateAllDescriptions(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index();
        $generated = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($pages as $page) {
            if (self::hasFieldInCurrentLanguage($page, 'metaDescription')) {
                $skipped++;
                continue;
            }

            $result = self::generateDescription($page->id());
            $result['status'] === 'success' ? $generated++ : $failed++;
        }

        return ApiResponse::batch($generated, $skipped, $failed,
            "Generated {$generated} descriptions, skipped {$skipped}, failed {$failed}");
    }

    /**
     * Generate selected fields (title and/or description) for specified pages
     */
    public static function generateAllFields(
        bool  $generateTitle = false,
        bool  $generateDescription = false,
        bool  $generateOgTitle = false,
        bool  $generateOgDescription = false,
        array $pageIds = []
    ): array
    {
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
        $errors = [];

        foreach ($pages as $page) {
            $isSite = ($page instanceof \Kirby\Cms\Site);
            $pageSkipped = true;

            // Generate title if requested (both site and pages use flat fields)
            if ($generateTitle) {
                // Check current language specifically (not fallback)
                $hasTitle = self::hasFieldInCurrentLanguage($page, 'metaTitle');

                if (!$hasTitle) {
                    $pageId = $isSite ? 'site' : $page->id();
                    $result = self::generateField($pageId, 'metaTitle', null, true);

                    if ($result['status'] === 'success') {
                        $generated++;
                        $pageSkipped = false;
                    } else {
                        $failed++;
                        $errors[] = self::formatGenerationError($page, 'metaTitle', $result);
                        $pageSkipped = false;
                    }
                }
            }

            // Generate description if requested
            if ($generateDescription) {
                // Check current language specifically (not fallback)
                $hasDescription = self::hasFieldInCurrentLanguage($page, 'metaDescription');

                if (!$hasDescription) {
                    $pageId = $isSite ? 'site' : $page->id();
                    $result = self::generateField($pageId, 'metaDescription', null, true);

                    if ($result['status'] === 'success') {
                        $generated++;
                        $pageSkipped = false;
                    } else {
                        $failed++;
                        $errors[] = self::formatGenerationError($page, 'metaDescription', $result);
                        $pageSkipped = false;
                    }
                }
            }

            // Generate OG title if requested (pages only, site doesn't have OG title)
            if ($generateOgTitle && !$isSite) {
                // Check current language specifically (not fallback)
                $hasOgTitle = self::hasFieldInCurrentLanguage($page, 'ogTitle');

                if (!$hasOgTitle) {
                    $result = self::generateField($page->id(), 'ogTitle', null, true);

                    if ($result['status'] === 'success') {
                        $generated++;
                        $pageSkipped = false;
                    } else {
                        $failed++;
                        $errors[] = self::formatGenerationError($page, 'ogTitle', $result);
                        $pageSkipped = false;
                    }
                }
            }

            // Generate OG description if requested (pages only)
            if ($generateOgDescription && !$isSite) {
                // Check current language specifically (not fallback)
                $hasOgDescription = self::hasFieldInCurrentLanguage($page, 'ogDescription');

                if (!$hasOgDescription) {
                    $result = self::generateField($page->id(), 'ogDescription', null, true);

                    if ($result['status'] === 'success') {
                        $generated++;
                        $pageSkipped = false;
                    } else {
                        $failed++;
                        $errors[] = self::formatGenerationError($page, 'ogDescription', $result);
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
        if ($generateTitle) $fields[] = 'meta titles';
        if ($generateDescription) $fields[] = 'meta descriptions';
        if ($generateOgTitle) $fields[] = 'OG titles';
        if ($generateOgDescription) $fields[] = 'OG descriptions';
        $fieldText = implode(', ', $fields);

        $message = "Generated {$generated} field(s) ({$fieldText}), skipped {$skipped}, failed {$failed}";
        if ($errors !== []) {
            $message .= '. First error: ' . $errors[0]['message'];
        }

        return ApiResponse::batch($generated, $skipped, $failed, $message, $errors);
    }

    private static function formatGenerationError($page, string $fieldName, array $result): array
    {
        return [
            'pageId' => $page instanceof \Kirby\Cms\Site ? 'site' : $page->id(),
            'pageTitle' => $page->title()->value(),
            'field' => $fieldName,
            'message' => $result['message'] ?? 'Unknown generation error',
        ];
    }


    public static function applySingleField(string $pageId, string $fieldName, $value): array
    {
        $kirby = kirby();
        $page = self::getPageOrSite($pageId);
        $allowedFields = [
            'metaTitle',
            'metaDescription',
            'ogTitle',
            'ogDescription',
            'ogImage',
            'robots',
            'canonicalUrl',
            'metaAuthor',
        ];

        if (!$page) {
            return ApiResponse::notFound();
        }

        if (!in_array($fieldName, $allowedFields, true)) {
            return ApiResponse::error('Unsupported field name');
        }

        if (!self::canUpdateModel($page)) {
            return ApiResponse::error('Forbidden');
        }

        if ($pageId === 'site' && in_array($fieldName, ['ogTitle', 'ogDescription'], true)) {
            return ApiResponse::error('Site does not support page-specific OG fields');
        }

        try {
            $languageCode = $kirby->language()?->code();

            if ($fieldName === 'ogImage') {
                if (empty($value)) {
                    $page->update([$fieldName => []], $languageCode);
                } else {
                    $file = strpos($value, 'file://') === 0
                        ? $kirby->file(str_replace('file://', '', $value))
                        : $page->file($value);

                    if (!$file) {
                        return ApiResponse::error('Image file not found');
                    }
                    $page->update([$fieldName => [$file->uuid()->toString()]], $languageCode);
                }
            } else {
                $page->update([$fieldName => $value], $languageCode);
            }

            $updatedPage = PageDataBuilder::fromPageId($pageId, ['includeOgImage' => true]);

            return ApiResponse::success([
                'page' => $updatedPage,
                'siteSettings' => $pageId === 'site' ? self::getSiteSettings() : null
            ], 'Field updated successfully');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public static function getPagesWithContent(): array
    {
        $kirby = kirby();
        $site = $kirby->site();
        $pages = $site->index();

        $excludeTemplates = option('tearoom1.meta-kit.excludeTemplates', []);
        $excludeStatus = option('tearoom1.meta-kit.excludeStatus', []);
        if (!is_array($excludeTemplates)) {
            $excludeTemplates = [$excludeTemplates];
        }
        if (!is_array($excludeStatus)) {
            $excludeStatus = [$excludeStatus];
        }

        // Filter by specific page IDs if provided
        $pageIdsParam = get('pageIds');
        $includeSite = false;

        if (!empty($pageIdsParam)) {
            $pageIds = array_values(array_filter(explode(',', $pageIdsParam)));
            $includeSite = in_array('site', $pageIds, true);
            $pages = $pages->filter(fn($page) => in_array($page->id(), $pageIds, true));
        } else {
            $includeSite = true;
        }

        $result = [];
        $builderOptions = ['includeOgImage' => true];

        // Add site as first entry if needed
        if ($includeSite) {
            $result[] = PageDataBuilder::fromModel($site, $builderOptions);
        }

        // Add pages
        foreach ($pages as $page) {
            $template = $page->intendedTemplate()->name();
            $status = $page->status();
            if (in_array($template, $excludeTemplates, true) || in_array($status, $excludeStatus, true)) {
                continue;
            }

            $result[] = PageDataBuilder::fromModel($page, $builderOptions);
        }

        return ApiResponse::success($result);
    }

    public static function getSinglePage(string $pageId): array
    {
        $data = PageDataBuilder::fromPageId($pageId, ['includeOgImage' => true]);

        if ($data === null) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success($data);
    }

    public static function getContentPreviewForReview(string $pageId, int $maxLength = 8000): string
    {
        $page = self::getPageOrSite($pageId);
        if (!$page) {
            return '';
        }

        $content = self::getContentForGeneration($page, $pageId === 'site');
        return mb_substr(trim($content), 0, $maxLength);
    }

    public static function getReviewContext(string $pageId, int $contentMaxLength = 8000): ?array
    {
        $data = PageDataBuilder::fromPageId($pageId, ['includeOgImage' => true]);
        if ($data === null) {
            return null;
        }

        return [
            'id' => $data['id'],
            'title' => $data['title'],
            'template' => $data['template'],
            'panelUrl' => $data['panelUrl'],
            'language' => $data['language'] ?? null,
            'metaTitle' => $data['metaTitle'] ?? null,
            'metaDescription' => $data['metaDescription'] ?? null,
            'ogTitle' => $data['ogTitle'] ?? null,
            'ogDescription' => $data['ogDescription'] ?? null,
            'robots' => $data['robots'] ?? null,
            'content' => self::getContentPreviewForReview($pageId, $contentMaxLength),
        ];
    }

    public static function getReviewContexts(array $pageIds, int $maxPages = 20, int $perPageLength = 1200): array
    {
        $contexts = [];

        foreach (array_slice($pageIds, 0, $maxPages) as $pageId) {
            $context = self::getReviewContext($pageId, $perPageLength);
            if ($context) {
                $contexts[] = $context;
            }
        }

        return $contexts;
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
            $keyLower = strtolower($key);
            if (in_array($keyLower, ['title', 'slug', 'template', 'seo', 'ogimage', 'metatitle', 'metadescription', 'ogtitle', 'ogdescription', 'robots', 'canonicalurl', 'metaauthor'], true)) {
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

    public static function generateField(string $pageId, string $fieldName, ?string $language = null, bool $save = false): array
    {
        $kirby = kirby();
        $isSite = ($pageId === 'site');
        $page = self::getPageOrSite($pageId);

        if (!$page) {
            return ApiResponse::notFound();
        }

        if ($save && !self::canUpdateModel($page)) {
            return ApiResponse::error('Forbidden');
        }

        $previousLanguage = $kirby->language()?->code();
        $shouldRestoreLanguage = false;

        if ($language && $kirby->multilang()) {
            $kirby->setCurrentLanguage($language);
            $shouldRestoreLanguage = true;
        }

        try {
            $metaKit = new MetaKit($kirby);
            $languageCode = $language ?: $kirby->language()?->code();
            $content = self::getContentForGeneration($page, $isSite);

            $fieldTypeMap = [
                'metaTitle' => 'title',
                'ogTitle' => 'ogTitle',
                'metaDescription' => 'description',
                'ogDescription' => 'ogDescription'
            ];

            if (!isset($fieldTypeMap[$fieldName])) {
                return ApiResponse::error('Unsupported field name');
            }

            if ($isSite && in_array($fieldName, ['ogTitle', 'ogDescription'], true)) {
                return ApiResponse::error('Site does not support page-specific OG fields');
            }

            $context = [
                'language' => $languageCode,
                'fieldType' => $fieldTypeMap[$fieldName]
            ];

            if (!$isSite) {
                $context['template'] = $page->intendedTemplate()->name();
            }

            if (in_array($fieldName, ['metaTitle', 'ogTitle'])) {
                $context['page'] = $page;
                $result = $metaKit->generateTitle($content, $context);
            } else {
                $result = $metaKit->generateDescription($content, $context);
            }

            if (!$result) {
                return ApiResponse::error('Failed to generate content');
            }

            if ($save) {
                $page->update([$fieldName => $result], $languageCode);
                $fieldLabel = ucfirst(str_replace('meta', 'Meta ', $fieldName));
                return ApiResponse::generated($result, "{$fieldLabel} generated successfully");
            }

            return ApiResponse::generated($result);

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage());
        } finally {
            if ($shouldRestoreLanguage && $previousLanguage) {
                $kirby->setCurrentLanguage($previousLanguage);
            }
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

    /**
     * Check if a field has content in the current language (without fallback)
     *
     * Kirby's isNotEmpty() uses language fallback, so a German field appears
     * "not empty" if English has content. This method checks the actual
     * content for the current language specifically.
     *
     * @param \Kirby\Cms\Page|\Kirby\Cms\Site $model
     * @param string $fieldName
     * @return bool
     */
    public static function hasFieldInCurrentLanguage($model, string $fieldName): bool
    {
        $kirby = kirby();

        // Single language site - use normal check
        if (!$kirby->multilang()) {
            return $model->$fieldName()->isNotEmpty();
        }

        $languageCode = $kirby->language()?->code();
        if (!$languageCode) {
            return $model->$fieldName()->isNotEmpty();
        }

        $translation = $model->translation($languageCode);
        if (!$translation || !$translation->exists()) {
            return false;
        }

        // Read the raw content file for this language without Kirby's fallback.
        $content = $model->version('latest')->read($translation->language());
        $key = strtolower($fieldName);

        return isset($content[$key]) && !empty(trim((string)$content[$key]));
    }

    /**
     * Get field inheritance information for multilingual sites
     *
     * Returns info about whether a field value is inherited from the default language
     *
     * @param \Kirby\Cms\Page|\Kirby\Cms\Site $model
     * @param string $fieldName
     * @return array{inherited: bool, inheritedFrom: string|null, inheritedValue: string|null}
     */
    public static function getFieldInheritance($model, string $fieldName): array
    {
        $kirby = kirby();
        $noInheritance = ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null];

        // Single language site - no inheritance
        if (!$kirby->multilang()) {
            return $noInheritance;
        }

        $currentLang = $kirby->language();
        if (!$currentLang) {
            return $noInheritance;
        }

        // If we're on the default language, no inheritance possible
        if ($currentLang->isDefault()) {
            return $noInheritance;
        }

        // Check if current language has its own value
        if (self::hasFieldInCurrentLanguage($model, $fieldName)) {
            return $noInheritance;
        }

        // Check if default language has a value (which would be inherited)
        $defaultLang = $kirby->defaultLanguage();
        if (!$defaultLang) {
            return $noInheritance;
        }

        // Get the value from the default language
        $key = strtolower($fieldName);
        $defaultTranslation = $model->translation($defaultLang->code());

        if (!$defaultTranslation || !$defaultTranslation->exists()) {
            return $noInheritance;
        }

        $defaultContent = $model->version('latest')->read($defaultTranslation->language());
        if (!isset($defaultContent[$key]) || empty(trim((string)$defaultContent[$key]))) {
            return $noInheritance;
        }

        return [
            'inherited' => true,
            'inheritedFrom' => $defaultLang->name(),
            'inheritedValue' => (string)$defaultContent[$key]
        ];
    }
}
