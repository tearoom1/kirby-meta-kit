<?php

namespace TearoomOne\Fields;

use Exception;
use Kirby\Cms\ModelWithContent;
use Kirby\Form\Field\DisplayField;
use TearoomOne\MetaHelper;

/**
 * Search result and share previews of the page, including unsaved changes
 * (field type `seo-preview`; a section up to Meta Kit 4)
 */
class PreviewField extends DisplayField
{
    /**
     * Routes under `{model}/fields/{name}/`; the Panel component reloads
     * the preview with `meta` after a language switch
     */
    public function api(): array
    {
        $field = $this;

        return [
            [
                'pattern' => 'meta',
                'method' => 'GET',
                'action' => fn () => [
                    'label' => $field->label(),
                    'meta' => $field->meta(),
                ],
            ],
        ];
    }

    public function label(): string
    {
        if ($this->label === null || $this->label === []) {
            return $this->stringTemplateI18n('meta-kit.preview.title');
        }

        return parent::label();
    }

    /**
     * Title, descriptions and share image as search engines and
     * social networks would show them
     */
    public function meta(): array
    {
        $page = $this->model();
        $site = $page->kirby()->site();

        try {
            $page = static::withChanges($page);

            // Title and descriptions from the flat SEO fields of the page
            $title = MetaHelper::buildTitle($page, $site, 'meta');
            $description = MetaHelper::buildDescription($page, $site);

            $ogTitle = MetaHelper::buildTitle($page, $site, 'og');
            $ogDescription = MetaHelper::buildOgDescription($page, $site, $description);

            // Share image of the page, else the site's default
            $ogImage = static::shareImage($page) ?? static::shareImage($site);

            return [
                'url' => $page->url(),
                'title' => static::decode($title),
                'description' => static::decode($description),
                'ogTitle' => static::decode($ogTitle),
                'ogDescription' => static::decode($ogDescription),
                'ogImage' => $ogImage,
            ];
        } catch (Exception $e) {
            return [
                'url' => $page->url(),
                'title' => $page->title()->value() . ' | ' . $site->title()->value(),
                'description' => 'Error loading SEO data: ' . $e->getMessage(),
                'ogTitle' => $page->title()->value(),
                'ogDescription' => '',
                'ogImage' => null,
            ];
        }
    }

    public function props(): array
    {
        return [
            ...parent::props(),
            'meta' => $this->meta(),
        ];
    }

    protected static function decode(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Cropped URL of the model's share image, if it has one
     */
    protected static function shareImage(ModelWithContent $model): string|null
    {
        if ($model->ogImage()->isEmpty()) {
            return null;
        }

        $image = $model->ogImage()->toFiles()->first();

        return $image?->crop(1200, 630)->url();
    }

    /**
     * The model with its unsaved changes of the current language
     */
    protected static function withChanges(ModelWithContent $model): ModelWithContent
    {
        $changes = $model->version('changes');
        $language = $model->kirby()->language()?->code() ?? 'current';

        if ($changes->exists($language) === false) {
            return $model;
        }

        return $model->clone([
            'content' => $changes->content($language)->toArray(),
        ]);
    }
}
