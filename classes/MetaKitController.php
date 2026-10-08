<?php

namespace TearoomOne;

class MetaKitController
{
    /**
     * Avoid short text, numbers and file strings
     */
    const MIN_TEXT_LENGTH = 25;

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

    /**
     * Whether a page is hidden from Meta Kit by the excludeTemplates or
     * excludeStatus option
     */
    public static function isExcluded($page): bool
    {
        $excludeTemplates = (array)option('tearoom1.meta-kit.excludeTemplates', []);
        $excludeStatus = (array)option('tearoom1.meta-kit.excludeStatus', []);

        return in_array($page->intendedTemplate()->name(), $excludeTemplates, true)
            || in_array($page->status(), $excludeStatus, true);
    }

    public static function getPages(): array
    {
        $kirby = kirby();
        $pages = $kirby->site()->index(true);
        $result = [];


        $languageCode = $kirby->language()?->code();

        // Add Site as first row
        $result[] = PageDataBuilder::fromModel($kirby->site());

        // Add pages
        foreach ($pages as $page) {
            if (self::isExcluded($page)) {
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
            'validationSettings' => ConfigHelper::getValidationSettings(),
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
     * Fields that bulk generation can fill, with their label for messages
     */
    private const GENERATABLE_FIELDS = [
        'metaTitle' => 'meta titles',
        'metaDescription' => 'meta descriptions',
        'ogTitle' => 'OG titles',
        'ogDescription' => 'OG descriptions',
    ];

    /**
     * Generate the selected fields for the given pages, skipping fields that
     * already have a value in the current language
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
        $requested = array_keys(array_filter([
            'metaTitle' => $generateTitle,
            'metaDescription' => $generateDescription,
            'ogTitle' => $generateOgTitle,
            'ogDescription' => $generateOgDescription,
        ]));

        // Get pages to process
        if (empty($pageIds)) {
            $pages = $kirby->site()->index();
        } else {
            $pages = array_filter(array_map(
                fn ($pageId) => self::getPageOrSite($pageId),
                $pageIds
            ));
        }

        $generated = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($pages as $page) {
            $isSite = ($page instanceof \Kirby\Cms\Site);
            $pageId = $isSite ? 'site' : $page->id();
            $pageSkipped = true;

            foreach ($requested as $fieldName) {
                // The site has no OG title/description of its own
                if ($isSite && str_starts_with($fieldName, 'og')) {
                    continue;
                }

                // Check current language specifically (not fallback)
                if (self::hasFieldInCurrentLanguage($page, $fieldName)) {
                    continue;
                }

                $pageSkipped = false;
                $result = self::generateField($pageId, $fieldName, null, true);

                if ($result['status'] === 'success') {
                    $generated++;
                } else {
                    $failed++;
                    $errors[] = self::formatGenerationError($page, $fieldName, $result);
                }
            }

            if ($pageSkipped) {
                $skipped++;
            }
        }

        $fieldText = implode(', ', array_map(fn ($field) => self::GENERATABLE_FIELDS[$field], $requested));
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
            if (self::isExcluded($page)) {
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
     * Model whose content describes a page or the site
     * (the site is described by its home page)
     */
    private static function getContentSource($page, bool $isSite = false)
    {
        return $isSite ? (kirby()->site()->homePage() ?? $page) : $page;
    }

    /**
     * Get content for AI generation: the title plus the page text
     * (for the site, the home page's)
     */
    public static function getContentForGeneration($page, bool $isSite = false): string
    {
        $source = self::getContentSource($page, $isSite);
        $body = self::extractPageContent($source);

        return trim($source->title()->value() . "\n\n" . $body);
    }

    /**
     * Minimum amount of page text (title excluded) needed for AI generation;
     * below it the model has nothing to describe and would make things up
     */
    public static function minContentLength(): int
    {
        return (int)option('tearoom1.meta-kit.ai.minContentLength', 50);
    }

    /**
     * Whether a page has enough text of its own for AI generation
     */
    public static function hasEnoughContent($page, bool $isSite = false): bool
    {
        $body = self::extractPageContent(self::getContentSource($page, $isSite));
        return mb_strlen(trim($body)) >= self::minContentLength();
    }

    /**
     * Field types whose values are not page text
     */
    private const NON_TEXT_FIELD_TYPES = [
        'checkboxes', 'color', 'date', 'email', 'files', 'gap', 'headline',
        'hidden', 'info', 'line', 'link', 'multiselect', 'number', 'pages',
        'radio', 'range', 'select', 'slug', 'tel', 'time', 'toggle', 'toggles',
        'url', 'users',
        'mk-title', 'mk-description', 'mk-review', 'mk-slug-info',
    ];

    /**
     * Content field keys (lowercase) that hold page text. Only fields defined
     * in the page's blueprint count, so leftovers from an earlier blueprint
     * that are still in the content file are ignored. Pages without a
     * blueprint of their own use all their fields: Kirby then falls back to
     * its core default, which has neither fields nor sections.
     */
    private static function textFieldKeys($page): array
    {
        $blueprint = $page->blueprint();
        $fields = $blueprint->fields();

        if ($fields === [] && $blueprint->sections() === []) {
            return array_keys($page->content()->fields());
        }

        $keys = [];
        foreach ($fields as $name => $field) {
            if (!in_array($field['type'] ?? 'text', self::NON_TEXT_FIELD_TYPES, true)) {
                $keys[] = strtolower($name);
            }
        }

        return $keys;
    }

    /**
     * Extract the text content of a page (title excluded)
     */
    private static function extractPageContent($page): string
    {
        $texts = [];

        foreach (self::textFieldKeys($page) as $keyLower) {
            if (in_array($keyLower, ['title', 'slug', 'template', 'seo', 'ogimage', 'metatitle', 'metadescription', 'ogtitle', 'ogdescription', 'robots', 'canonicalurl', 'metaauthor'], true)) {
                continue;
            }

            $value = $page->content()->get($keyLower);
            if ($value->isEmpty()) {
                continue;
            }

            // Get raw value
            $rawValue = $value->value();
            if (!is_string($rawValue)) {
                continue;
            }

            // skip files
            if (str_starts_with(trim($rawValue), 'file:')) {
                continue;
            }

            // Check if it's JSON (blocks, layout, structure)
            if (str_starts_with(trim($rawValue), '[') || str_starts_with(trim($rawValue), '{')) {
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

            if (!self::hasEnoughContent($page, $isSite)) {
                return ApiResponse::error(
                    'Not enough text on this page to generate metadata. Add some content or write it manually.'
                );
            }

            $content = self::getContentForGeneration($page, $isSite);

            $context = [
                'language' => $languageCode ?? MetaHelper::currentLanguageCode($kirby),
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
