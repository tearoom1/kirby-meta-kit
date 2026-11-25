<?php

namespace TearoomOne\Fields;

use Kirby\Form\Field\TextField;
use Kirby\Reflection\Attributes\Prop;
use TearoomOne\ConfigHelper;
use TearoomOne\MetaKit;

/**
 * Meta or Open Graph title with length check, title preview and AI button
 * (field type `mk-title`)
 */
class TitleField extends TextField
{
    /**
     * `meta` for the meta title, `og` for the Open Graph title
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
     * Length ranges, site name settings and the page context for the Panel
     */
    public function validationSettings(): array
    {
        $model = $this->model();
        $template = FieldContext::template($model);
        $siteSettings = ConfigHelper::getSiteSettings();
        $rangeType = $this->fieldType === 'og' ? 'ogTitle' : 'title';

        return [
            'ranges' => ConfigHelper::getValidationRanges($rangeType, FieldContext::rulesTemplate($model)),
            'appendSiteName' => $siteSettings['appendSiteName'],
            'appendSiteNameTo' => $siteSettings['appendSiteNameTo'],
            'siteMetaTitle' => $siteSettings['siteMetaTitle'],
            'titleSeparator' => $siteSettings['titleSeparator'],
            'fieldType' => $this->fieldType,
            'pageId' => $this->pageId ?? FieldContext::pageId($model),
            'template' => $template,
        ];
    }
}
