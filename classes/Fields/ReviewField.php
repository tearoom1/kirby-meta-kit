<?php

namespace TearoomOne\Fields;

use Kirby\Form\Field\InfoField;
use TearoomOne\MetaKit;

/**
 * Button for the AI content review of the page or site
 * (field type `mk-review`)
 */
class ReviewField extends InfoField
{
    protected string|null $theme = 'info';

    public function label(): string
    {
        if ($this->label === null || $this->label === []) {
            return $this->stringTemplateI18n('meta-kit.bp.meta-group.seoReview.label');
        }

        return parent::label();
    }

    public function props(): array
    {
        return [
            ...parent::props(),
            'pageId' => FieldContext::pageId($this->model()),
            'aiEnabled' => MetaKit::isAiEnabled(),
            'reviewEnabled' => MetaKit::isReviewEnabled(),
        ];
    }
}
