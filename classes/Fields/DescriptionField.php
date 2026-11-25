<?php

namespace TearoomOne\Fields;

use Kirby\Form\Field\TextareaField;
use Kirby\Reflection\Attributes\Prop;
use TearoomOne\ConfigHelper;
use TearoomOne\MetaKit;

/**
 * Meta or Open Graph description with length check and AI button
 * (field type `mk-description`)
 */
class DescriptionField extends TextareaField
{
    /**
     * `meta` for the meta description, `og` for the Open Graph description
     */
    #[Prop]
    protected string $fieldType = 'meta';

    /**
     * Page the AI button generates for; defaults to the field's model
     */
    #[Prop]
    protected string|null $pageId = null;

    public function props(): array
    {
        return [
            ...parent::props(),
            'validationSettings' => $this->validationSettings(),
            'aiEnabled' => MetaKit::isAiEnabled(),
        ];
    }

    /**
     * Length ranges and the page context for the Panel
     */
    public function validationSettings(): array
    {
        $model = $this->model();
        $template = FieldContext::template($model);
        $rangeType = $this->fieldType === 'og' ? 'ogDescription' : 'description';

        return [
            'ranges' => ConfigHelper::getValidationRanges($rangeType, $template === 'site' ? null : $template),
            'fieldType' => $this->fieldType,
            'pageId' => $this->pageId ?? FieldContext::pageId($model),
            'template' => $template,
        ];
    }
}
