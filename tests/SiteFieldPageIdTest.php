<?php

namespace TearoomOne\Tests;

use TearoomOne\Fields\DescriptionField;
use TearoomOne\Fields\TitleField;

/**
 * The generate button of the site-level mk-title/mk-description fields
 * must send 'site' as pageId, since Site::id() is null.
 */
class SiteFieldPageIdTest extends KirbyTestCase
{
    public static function fieldTypes(): array
    {
        return [['mk-title', TitleField::class], ['mk-description', DescriptionField::class]];
    }

    /**
     * @dataProvider fieldTypes
     */
    public function testSiteFieldSendsSiteAsPageId(string $type, string $class): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => 'Title: Test Site',
            'about/default.txt' => 'Title: About',
        ]);

        $siteField = new $class(model: $kirby->site(), name: 'metaTitle');
        $pageField = new $class(model: $kirby->page('about'), name: 'metaTitle');

        $this->assertSame('site', $siteField->toArray()['validationSettings']['pageId']);
        $this->assertSame('about', $pageField->toArray()['validationSettings']['pageId']);
    }
}
