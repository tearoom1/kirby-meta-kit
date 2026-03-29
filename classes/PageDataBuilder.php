<?php

namespace TearoomOne;

/**
 * Builder class for constructing page/site data arrays
 * Centralizes the logic for building SEO data responses
 */
class PageDataBuilder
{
    private $model;
    private bool $isSite;
    private ?string $languageCode;
    private bool $includeOgImage;

    /**
     * Legacy field mappings to check for migration
     */
    private const LEGACY_TITLE_FIELDS = ['customtitle', 'seotitle'];
    private const LEGACY_DESC_FIELDS = ['seodescription'];

    public function __construct($model, array $options = [])
    {
        $this->model = $model;
        $this->isSite = ($model instanceof \Kirby\Cms\Site);
        $this->languageCode = kirby()->language()?->code();
        $this->includeOgImage = $options['includeOgImage'] ?? false;
    }

    /**
     * Build the complete page data array
     */
    public function build(): array
    {
        $data = $this->buildBaseData();
        $data = array_merge($data, $this->buildMetaFields());
        $data = array_merge($data, $this->buildOgFields());

        if ($this->includeOgImage) {
            $data['ogImage'] = $this->buildOgImageData();
        }

        $data['legacy'] = $this->buildLegacyData();

        return $data;
    }

    /**
     * Build base data common to all pages/site
     */
    private function buildBaseData(): array
    {
        if ($this->isSite) {
            return [
                'id' => 'site',
                'title' => $this->model->title()->value(),
                'url' => $this->model->url(),
                'panelUrl' => $this->model->panel()->url(),
                'template' => 'site',
                'status' => 'published',
                'language' => $this->languageCode,
            ];
        }

        return [
            'id' => $this->model->id(),
            'title' => $this->model->title()->value(),
            'url' => $this->model->url(),
            'panelUrl' => $this->model->panel()->url(),
            'template' => $this->model->intendedTemplate()->name(),
            'status' => $this->model->status(),
            'language' => $this->languageCode,
        ];
    }

    /**
     * Build meta title and description fields
     */
    private function buildMetaFields(): array
    {
        $metaTitle = ConfigHelper::getString($this->model->metaTitle());
        $metaDescription = ConfigHelper::getString($this->model->metaDescription());

        return [
            'hasMetaTitle' => MetaKitController::hasFieldInCurrentLanguage($this->model, 'metaTitle'),
            'hasMetaDescription' => MetaKitController::hasFieldInCurrentLanguage($this->model, 'metaDescription'),
            'robots' => ConfigHelper::getString($this->model->robots(), 'index, follow'),
            'metaTitle' => $metaTitle ?: null,
            'metaTitleLength' => mb_strlen($metaTitle),
            'metaTitleInheritance' => MetaKitController::getFieldInheritance($this->model, 'metaTitle'),
            'metaDescription' => $metaDescription ?: null,
            'metaDescriptionLength' => mb_strlen($metaDescription),
            'metaDescriptionInheritance' => MetaKitController::getFieldInheritance($this->model, 'metaDescription'),
        ];
    }

    /**
     * Build OG title and description fields
     */
    private function buildOgFields(): array
    {
        $noInheritance = ['inherited' => false, 'inheritedFrom' => null, 'inheritedValue' => null];

        // Site doesn't have OG title/description fields
        if ($this->isSite) {
            return [
                'hasOgTitle' => false,
                'hasOgDescription' => false,
                'hasOgImage' => MetaKitController::hasFieldInCurrentLanguage($this->model, 'ogImage'),
                'ogTitle' => null,
                'ogTitleLength' => 0,
                'ogTitleInheritance' => $noInheritance,
                'ogDescription' => null,
                'ogDescriptionLength' => 0,
                'ogDescriptionInheritance' => $noInheritance,
            ];
        }

        $ogTitle = ConfigHelper::getString($this->model->ogTitle());
        $ogDescription = ConfigHelper::getString($this->model->ogDescription());

        return [
            'hasOgTitle' => MetaKitController::hasFieldInCurrentLanguage($this->model, 'ogTitle'),
            'hasOgDescription' => MetaKitController::hasFieldInCurrentLanguage($this->model, 'ogDescription'),
            'hasOgImage' => MetaKitController::hasFieldInCurrentLanguage($this->model, 'ogImage'),
            'ogTitle' => $ogTitle ?: null,
            'ogTitleLength' => mb_strlen($ogTitle),
            'ogTitleInheritance' => MetaKitController::getFieldInheritance($this->model, 'ogTitle'),
            'ogDescription' => $ogDescription ?: null,
            'ogDescriptionLength' => mb_strlen($ogDescription),
            'ogDescriptionInheritance' => MetaKitController::getFieldInheritance($this->model, 'ogDescription'),
        ];
    }

    /**
     * Build OG image data (filename, url, uuid)
     */
    private function buildOgImageData(): ?array
    {
        if ($this->model->ogImage()->isEmpty()) {
            return null;
        }

        $ogFiles = $this->model->ogImage()->toFiles();
        if ($ogFiles->count() === 0) {
            return null;
        }

        $ogFile = $ogFiles->first();
        return [
            'filename' => $ogFile->filename(),
            'url' => $ogFile->url(),
            'uuid' => $ogFile->uuid()?->toString()
        ];
    }

    /**
     * Build legacy field data for migration support
     */
    private function buildLegacyData(): ?array
    {
        $legacy = [];

        // Check legacy title fields
        foreach (self::LEGACY_TITLE_FIELDS as $field) {
            if (empty($legacy['metaTitle']) && $this->model->$field()->isNotEmpty()) {
                $legacy['metaTitle'] = $this->model->$field()->value();
            }
        }

        // Check legacy description fields
        foreach (self::LEGACY_DESC_FIELDS as $field) {
            if (empty($legacy['metaDescription']) && $this->model->$field()->isNotEmpty()) {
                $legacy['metaDescription'] = $this->model->$field()->value();
            }
        }

        return !empty($legacy) ? $legacy : null;
    }

    /**
     * Static factory method to build page data from a page ID
     */
    public static function fromPageId(string $pageId, array $options = []): ?array
    {
        $kirby = kirby();

        if ($pageId === 'site') {
            $model = $kirby->site();
        } else {
            $model = $kirby->page($pageId);
            if (!$model) {
                return null;
            }
        }

        return (new self($model, $options))->build();
    }

    /**
     * Static factory method to build page data from a model
     */
    public static function fromModel($model, array $options = []): array
    {
        return (new self($model, $options))->build();
    }
}
