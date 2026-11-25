<?php

namespace TearoomOne\Fields;

use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Site;

/**
 * Page id and template of a field's model, as the Panel components expect them
 */
class FieldContext
{
    /**
     * `site` for the site, since Site::id() is null
     */
    public static function pageId(ModelWithContent $model): string|null
    {
        return $model instanceof Site ? 'site' : $model->id();
    }

    /**
     * Template name of a page, `site` for the site and other models
     */
    public static function template(ModelWithContent $model): string
    {
        if (method_exists($model, 'intendedTemplate') === true) {
            return $model->intendedTemplate()->name();
        }

        return 'site';
    }

    /**
     * Template whose validation rules apply: the page's template, null
     * (global rules) for the site and other models. A page template may be
     * called `site` too, so the model decides, not the name.
     */
    public static function rulesTemplate(ModelWithContent $model): string|null
    {
        if (method_exists($model, 'intendedTemplate') === true) {
            return $model->intendedTemplate()->name();
        }

        return null;
    }
}
