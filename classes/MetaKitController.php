<?php

namespace TearoomOne;

class MetaKitController
{
    /**
     * Avoid short text, numbers and file strings
     */
    const int MIN_TEXT_LENGTH = 25;

    /**
     * Convert SEO data to array for saving (legacy support)
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

        // Get current language
        $language = $kirby->language();
        $languageCode = $language ? $language->code() : null;

        // Add Site as first row (site uses flat fields)
        $site = $kirby->site();
        $siteLegacy = [];
        if ($site->metatitle()->isNotEmpty()) {
            $siteLegacy['metaTitle'] = $site->metatitle()->value();
        }
        if ($site->metadescription()->isNotEmpty()) {
            $siteLegacy['metaDescription'] = $site->metadescription()->value();
        }
        // Get inheritance info for site fields
        $siteMetaTitleInheritance = self::getFieldInheritance($site, 'metaTitle');
        $siteMetaDescInheritance = self::getFieldInheritance($site, 'metaDescription');

        $result[] = [
            'id' => 'site',
            'title' => $site->title()->value(),
            'url' => $site->url(),
            'panelUrl' => $site->panel()->url(),
            'template' => 'site',
            'status' => 'published',
            'hasMetaTitle' => self::hasFieldInCurrentLanguage($site, 'metaTitle'),
            'hasMetaDescription' => self::hasFieldInCurrentLanguage($site, 'metaDescription'),
            'hasOgTitle' => false, // Site doesn't have ogTitle
            'hasOgDescription' => false, // Site doesn't have ogDescription
            'hasOgImage' => self::hasFieldInCurrentLanguage($site, 'ogImage'),
            'robots' => $site->robots()->isNotEmpty() ? $site->robots()->value() : 'index, follow',
            'metaTitle' => $site->metaTitle()->isNotEmpty()
                ? $site->metaTitle()->value()
                : null,
            'metaTitleLength' => $site->metaTitle()->isNotEmpty()
                ? mb_strlen($site->metaTitle()->value())
                : 0,
            'metaTitleInheritance' => $siteMetaTitleInheritance,
            'metaDescription' => $site->metaDescription()->isNotEmpty()
                ? $site->metaDescription()->value()
                : null,
            'metaDescriptionLength' => $site->metaDescription()->isNotEmpty()
                ? mb_strlen($site->metaDescription()->value())
                : 0,
            'metaDescriptionInheritance' => $siteMetaDescInheritance,
            'ogTitle' => null,
            'ogTitleLength' => 0,
            'ogTitleInheritance' => ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null],
            'ogDescription' => null,
            'ogDescriptionLength' => 0,
            'ogDescriptionInheritance' => ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null],
            'language' => $languageCode,
            'legacy' => !empty($siteLegacy) ? $siteLegacy : null,
        ];

        // Pages use flat fields
        foreach ($pages as $page) {
            $template = $page->intendedTemplate()->name();
            $status = $page->status();
            if (in_array($template, $excludeTemplates, true) || in_array($status, $excludeStatus, true)) {
                continue;
            }

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

            // Get inheritance info for page fields
            $metaTitleInheritance = self::getFieldInheritance($page, 'metaTitle');
            $metaDescInheritance = self::getFieldInheritance($page, 'metaDescription');
            $ogTitleInheritance = self::getFieldInheritance($page, 'ogTitle');
            $ogDescInheritance = self::getFieldInheritance($page, 'ogDescription');

            $result[] = [
                'id' => $page->id(),
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => $template,
                'status' => $status,
                'hasMetaTitle' => self::hasFieldInCurrentLanguage($page, 'metaTitle'),
                'hasMetaDescription' => self::hasFieldInCurrentLanguage($page, 'metaDescription'),
                'hasOgTitle' => self::hasFieldInCurrentLanguage($page, 'ogTitle'),
                'hasOgDescription' => self::hasFieldInCurrentLanguage($page, 'ogDescription'),
                'hasOgImage' => self::hasFieldInCurrentLanguage($page, 'ogImage'),
                'robots' => $page->robots()->isNotEmpty() ? $page->robots()->value() : 'index, follow',
                'metaTitle' => $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null,
                'metaTitleLength' => $page->metaTitle()->isNotEmpty()
                    ? mb_strlen($page->metaTitle()->value())
                    : 0,
                'metaTitleInheritance' => $metaTitleInheritance,
                'metaDescription' => $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null,
                'metaDescriptionLength' => $page->metaDescription()->isNotEmpty()
                    ? mb_strlen($page->metaDescription()->value())
                    : 0,
                'metaDescriptionInheritance' => $metaDescInheritance,
                'ogTitle' => $page->ogTitle()->isNotEmpty()
                    ? $page->ogTitle()->value()
                    : null,
                'ogTitleLength' => $page->ogTitle()->isNotEmpty()
                    ? mb_strlen($page->ogTitle()->value())
                    : 0,
                'ogTitleInheritance' => $ogTitleInheritance,
                'ogDescription' => $page->ogDescription()->isNotEmpty()
                    ? $page->ogDescription()->value()
                    : null,
                'ogDescriptionLength' => $page->ogDescription()->isNotEmpty()
                    ? mb_strlen($page->ogDescription()->value())
                    : 0,
                'ogDescriptionInheritance' => $ogDescInheritance,
                'language' => $languageCode,
                'legacy' => !empty($legacy) ? $legacy : null,
            ];
        }

        // Get site SEO settings for title preview (flat fields)
        $appendSiteName = $site->appendSiteName()->isNotEmpty()
            ? $site->appendSiteName()->toBool()
            : true;
        $appendSiteNameTo = $site->appendSiteNameTo()->isNotEmpty()
            ? $site->appendSiteNameTo()->value()
            : null;
        $siteMetaTitle = $site->metaTitle()->isNotEmpty()
            ? $site->metaTitle()->value()
            : $site->title()->value();
        $siteMetaDescription = $site->metaDescription()->isNotEmpty()
            ? $site->metaDescription()->value()
            : null;
        $siteHasOgImage = $site->ogImage()->isNotEmpty();
        $titleSeparator = $site->titleSeparator()->isNotEmpty()
            ? $site->titleSeparator()->value()
            : '|';

        $validationSettings = option('tearoom1.meta-kit.validation', []);

        return [
            'language' => $languageCode,
            'languages' => self::getLanguages(),
            'pages' => $result,
            'legacyMigration' => option('tearoom1.meta-kit.legacyMigration', false),
            'aiEnabled' => \TearoomOne\MetaKit::isAiEnabled(),
            'hasValidLicense' => \TearoomOne\MetaKit::hasValidLicense(),
            'validationSettings' => $validationSettings,
            'siteSettings' => [
                'appendSiteName' => $appendSiteName,
                'appendSiteNameTo' => $appendSiteNameTo,
                'siteMetaTitle' => $siteMetaTitle,
                'siteMetaDescription' => $siteMetaDescription,
                'siteHasOgImage' => $siteHasOgImage,
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
            // Skip if already has description in current language (not fallback)
            if (self::hasFieldInCurrentLanguage($page, 'metaDescription')) {
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

        return [
            'status' => 'success',
            'message' => "Generated {$generated} field(s) ({$fieldText}), skipped {$skipped}, failed {$failed}",
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
            $languageCode = $kirby->language()?->code();

            // Both site and pages use flat fields now
            if ($fieldName === 'ogImage') {
                if (empty($value)) {
                    $page->update([$fieldName => []], $languageCode);
                } else {
                    $file = null;
                    if (strpos($value, 'file://') === 0) {
                        $file = $kirby->file(str_replace('file://', '', $value));
                    } else {
                        $file = $page->file($value);
                    }

                    if ($file) {
                        $page->update([$fieldName => [$file->uuid()->toString()]], $languageCode);
                    } else {
                        return [
                            'status' => 'error',
                            'message' => 'Image file not found'
                        ];
                    }
                }
            } else {
                $page->update([$fieldName => $value], $languageCode);
            }

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

        $excludeTemplates = option('tearoom1.meta-kit.excludeTemplates', []);
        $excludeStatus = option('tearoom1.meta-kit.excludeStatus', []);
        if (!is_array($excludeTemplates)) {
            $excludeTemplates = [$excludeTemplates];
        }
        if (!is_array($excludeStatus)) {
            $excludeStatus = [$excludeStatus];
        }

        // Filter by specific page IDs if provided
        $pageIds = explode(',', get('pageIds'));
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

        // Add site as first entry if needed (site uses flat fields)
        if ($includeSite) {
            $siteLegacy = [];

            if ($site->metatitle()->isNotEmpty()) {
                $siteLegacy['metaTitle'] = $site->metatitle()->value();
            }
            if ($site->metadescription()->isNotEmpty()) {
                $siteLegacy['metaDescription'] = $site->metadescription()->value();
            }

            $ogImageData = null;
            if ($site->ogImage()->isNotEmpty()) {
                $ogFiles = $site->ogImage()->toFiles();
                if ($ogFiles->count() > 0) {
                    $ogFile = $ogFiles->first();
                    $ogImageData = [
                        'filename' => $ogFile->filename(),
                        'url' => $ogFile->url(),
                        'uuid' => $ogFile->uuid()?->toString()
                    ];
                }
            }

            // Get inheritance info for site fields
            $siteMetaTitleInheritance = self::getFieldInheritance($site, 'metaTitle');
            $siteMetaDescInheritance = self::getFieldInheritance($site, 'metaDescription');

            $result[] = [
                'id' => 'site',
                'title' => $site->title()->value(),
                'url' => $site->url(),
                'panelUrl' => $site->panel()->url(),
                'template' => 'site',
                'status' => 'published',
                'hasMetaTitle' => self::hasFieldInCurrentLanguage($site, 'metaTitle'),
                'hasMetaDescription' => self::hasFieldInCurrentLanguage($site, 'metaDescription'),
                'hasOgTitle' => false,
                'hasOgDescription' => false,
                'hasOgImage' => self::hasFieldInCurrentLanguage($site, 'ogImage'),
                'robots' => $site->robots()->isNotEmpty() ? $site->robots()->value() : 'index, follow',
                'metaTitle' => $site->metaTitle()->isNotEmpty()
                    ? $site->metaTitle()->value()
                    : null,
                'metaTitleInheritance' => $siteMetaTitleInheritance,
                'metaDescription' => $site->metaDescription()->isNotEmpty()
                    ? $site->metaDescription()->value()
                    : null,
                'metaDescriptionInheritance' => $siteMetaDescInheritance,
                'ogTitle' => null,
                'ogTitleInheritance' => ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null],
                'ogDescription' => null,
                'ogDescriptionInheritance' => ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null],
                'ogImage' => $ogImageData,
                'metaTitleLength' => $site->metaTitle()->isNotEmpty()
                    ? mb_strlen($site->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $site->metaDescription()->isNotEmpty()
                    ? mb_strlen($site->metaDescription()->value())
                    : 0,
                'ogTitleLength' => 0,
                'ogDescriptionLength' => 0,
                'legacy' => !empty($siteLegacy) ? $siteLegacy : null,
            ];
        }

        // Pages use flat fields
        foreach ($pages as $page) {
            $template = $page->intendedTemplate()->name();
            $status = $page->status();
            if (in_array($template, $excludeTemplates, true) || in_array($status, $excludeStatus, true)) {
                continue;
            }

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
            if ($page->ogImage()->isNotEmpty()) {
                $ogFiles = $page->ogImage()->toFiles();
                if ($ogFiles->count() > 0) {
                    $ogFile = $ogFiles->first();
                    $ogImageData = [
                        'filename' => $ogFile->filename(),
                        'url' => $ogFile->url(),
                        'uuid' => $ogFile->uuid()?->toString()
                    ];
                }
            }

            // Get inheritance info for page fields
            $metaTitleInheritance = self::getFieldInheritance($page, 'metaTitle');
            $metaDescInheritance = self::getFieldInheritance($page, 'metaDescription');
            $ogTitleInheritance = self::getFieldInheritance($page, 'ogTitle');
            $ogDescInheritance = self::getFieldInheritance($page, 'ogDescription');

            $result[] = [
                'id' => $page->id(),
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => $template,
                'status' => $status,
                'hasMetaTitle' => self::hasFieldInCurrentLanguage($page, 'metaTitle'),
                'hasMetaDescription' => self::hasFieldInCurrentLanguage($page, 'metaDescription'),
                'hasOgTitle' => self::hasFieldInCurrentLanguage($page, 'ogTitle'),
                'hasOgDescription' => self::hasFieldInCurrentLanguage($page, 'ogDescription'),
                'hasOgImage' => self::hasFieldInCurrentLanguage($page, 'ogImage'),
                'robots' => $page->robots()->isNotEmpty() ? $page->robots()->value() : 'index, follow',
                'metaTitle' => $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null,
                'metaTitleInheritance' => $metaTitleInheritance,
                'metaDescription' => $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null,
                'metaDescriptionInheritance' => $metaDescInheritance,
                'ogTitle' => $page->ogTitle()->isNotEmpty()
                    ? $page->ogTitle()->value()
                    : null,
                'ogTitleInheritance' => $ogTitleInheritance,
                'ogDescription' => $page->ogDescription()->isNotEmpty()
                    ? $page->ogDescription()->value()
                    : null,
                'ogDescriptionInheritance' => $ogDescInheritance,
                'ogImage' => $ogImageData,
                'metaTitleLength' => $page->metaTitle()->isNotEmpty()
                    ? mb_strlen($page->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $page->metaDescription()->isNotEmpty()
                    ? mb_strlen($page->metaDescription()->value())
                    : 0,
                'ogTitleLength' => $page->ogTitle()->isNotEmpty()
                    ? mb_strlen($page->ogTitle()->value())
                    : 0,
                'ogDescriptionLength' => $page->ogDescription()->isNotEmpty()
                    ? mb_strlen($page->ogDescription()->value())
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
        if ($page->ogImage()->isNotEmpty()) {
            $ogFiles = $page->ogImage()->toFiles();
            if ($ogFiles->count() > 0) {
                $ogFile = $ogFiles->first();
                $ogImageData = [
                    'filename' => $ogFile->filename(),
                    'url' => $ogFile->url(),
                    'uuid' => $ogFile->uuid()?->toString()
                ];
            }
        }

        // Get inheritance info
        $metaTitleInheritance = self::getFieldInheritance($page, 'metaTitle');
        $metaDescInheritance = self::getFieldInheritance($page, 'metaDescription');
        $ogTitleInheritance = $isSite ? ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null] : self::getFieldInheritance($page, 'ogTitle');
        $ogDescInheritance = $isSite ? ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null] : self::getFieldInheritance($page, 'ogDescription');

        if ($isSite) {
            // Site uses flat fields (no ogTitle/ogDescription)
            $result = [
                'id' => 'site',
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => 'site',
                'status' => 'published',
                'hasMetaTitle' => self::hasFieldInCurrentLanguage($page, 'metaTitle'),
                'hasMetaDescription' => self::hasFieldInCurrentLanguage($page, 'metaDescription'),
                'hasOgTitle' => false,
                'hasOgDescription' => false,
                'hasOgImage' => self::hasFieldInCurrentLanguage($page, 'ogImage'),
                'robots' => $page->robots()->isNotEmpty() ? $page->robots()->value() : 'index, follow',
                'metaTitle' => $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null,
                'metaTitleInheritance' => $metaTitleInheritance,
                'metaDescription' => $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null,
                'metaDescriptionInheritance' => $metaDescInheritance,
                'ogTitle' => null,
                'ogTitleInheritance' => $ogTitleInheritance,
                'ogDescription' => null,
                'ogDescriptionInheritance' => $ogDescInheritance,
                'ogImage' => $ogImageData,
                'metaTitleLength' => $page->metaTitle()->isNotEmpty()
                    ? mb_strlen($page->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $page->metaDescription()->isNotEmpty()
                    ? mb_strlen($page->metaDescription()->value())
                    : 0,
                'ogTitleLength' => 0,
                'ogDescriptionLength' => 0,
                'legacy' => !empty($legacy) ? $legacy : null,
            ];
        } else {
            // Pages use flat fields
            $result = [
                'id' => $page->id(),
                'title' => $page->title()->value(),
                'url' => $page->url(),
                'panelUrl' => $page->panel()->url(),
                'template' => $page->intendedTemplate()->name(),
                'status' => $page->status(),
                'hasMetaTitle' => self::hasFieldInCurrentLanguage($page, 'metaTitle'),
                'hasMetaDescription' => self::hasFieldInCurrentLanguage($page, 'metaDescription'),
                'hasOgTitle' => self::hasFieldInCurrentLanguage($page, 'ogTitle'),
                'hasOgDescription' => self::hasFieldInCurrentLanguage($page, 'ogDescription'),
                'hasOgImage' => self::hasFieldInCurrentLanguage($page, 'ogImage'),
                'robots' => $page->robots()->isNotEmpty() ? $page->robots()->value() : 'index, follow',
                'metaTitle' => $page->metaTitle()->isNotEmpty()
                    ? $page->metaTitle()->value()
                    : null,
                'metaTitleInheritance' => $metaTitleInheritance,
                'metaDescription' => $page->metaDescription()->isNotEmpty()
                    ? $page->metaDescription()->value()
                    : null,
                'metaDescriptionInheritance' => $metaDescInheritance,
                'ogTitle' => $page->ogTitle()->isNotEmpty()
                    ? $page->ogTitle()->value()
                    : null,
                'ogTitleInheritance' => $ogTitleInheritance,
                'ogDescription' => $page->ogDescription()->isNotEmpty()
                    ? $page->ogDescription()->value()
                    : null,
                'ogDescriptionInheritance' => $ogDescInheritance,
                'ogImage' => $ogImageData,
                'metaTitleLength' => $page->metaTitle()->isNotEmpty()
                    ? mb_strlen($page->metaTitle()->value())
                    : 0,
                'metaDescriptionLength' => $page->metaDescription()->isNotEmpty()
                    ? mb_strlen($page->metaDescription()->value())
                    : 0,
                'ogTitleLength' => $page->ogTitle()->isNotEmpty()
                    ? mb_strlen($page->ogTitle()->value())
                    : 0,
                'ogDescriptionLength' => $page->ogDescription()->isNotEmpty()
                    ? mb_strlen($page->ogDescription()->value())
                    : 0,
                'legacy' => !empty($legacy) ? $legacy : null,
            ];
        }

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
            if (in_array($key, ['title', 'slug', 'template', 'seo', 'ogimage', 'metatitle', 'metadescription', 'ogtitle', 'ogdescription', 'robots', 'canonicalurl', 'metaauthor'])) {
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

            // Map fieldName to fieldType
            $fieldTypeMap = [
                'metaTitle' => 'title',
                'ogTitle' => 'ogTitle',
                'metaDescription' => 'description',
                'ogDescription' => 'ogDescription'
            ];

            if (!isset($fieldTypeMap[$fieldName])) {
                return [
                    'status' => 'error',
                    'message' => 'Unsupported field name'
                ];
            }

            $fieldType = $fieldTypeMap[$fieldName];

            // Build context for generation
            $context = [
                'language' => $languageCode,
                'fieldType' => $fieldType
            ];

            // Add template for pages (not site)
            if (!$isSite) {
                $context['template'] = $page->intendedTemplate()->name();
            }

            if ($fieldName === 'metaTitle' || $fieldName === 'ogTitle') {
                // Add page reference for title generation (used in calculateTargetTitleLength)
                $context['page'] = $page;
                $result = $metaKit->generateTitle($content, $context);
            } elseif ($fieldName === 'metaDescription' || $fieldName === 'ogDescription') {
                $result = $metaKit->generateDescription($content, $context);
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Unsupported field name'
                ];
            }

            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to generate content'
                ];
            }

            // Save if requested - both site and pages use flat fields
            if ($save) {
                $page->update([$fieldName => $result], $languageCode);

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

        // For Site object
        if ($model instanceof \Kirby\Cms\Site) {
            $translation = $model->translation($languageCode);
            if (!$translation || !$translation->exists()) {
                return false;
            }
            $content = $translation->content();
            $key = strtolower($fieldName);
            return isset($content[$key]) && !empty(trim((string)$content[$key]));
        }

        // For Page object - check if the field exists in this specific language's content
        $translation = $model->translation($languageCode);
        if (!$translation || !$translation->exists()) {
            return false;
        }

        $content = $translation->content();
        // Field names are stored lowercase in Kirby
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

        $defaultContent = $defaultTranslation->content();
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
