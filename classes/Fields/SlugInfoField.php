<?php

namespace TearoomOne\Fields;

use Kirby\Form\Field\DisplayField;
use Kirby\Reflection\Attributes\Prop;
use TearoomOne\ConfigHelper;

/**
 * Checks the page slug against the slug rules; stores nothing
 * (field type `mk-slug-info`)
 */
class SlugInfoField extends DisplayField
{
    /**
     * Slug to check instead of the page's own
     */
    #[Prop]
    protected string|null $slug = null;

    public function props(): array
    {
        $model = $this->model();
        $template = FieldContext::template($model);

        return [
            ...parent::props(),
            'currentSlug' => $this->slug ?? (method_exists($model, 'slug') ? $model->slug() : null),
            'validationSettings' => [
                ...ConfigHelper::getSlugValidation($template),
                'template' => $template,
            ],
        ];
    }
}
