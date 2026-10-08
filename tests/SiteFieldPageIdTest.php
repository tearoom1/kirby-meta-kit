<?php

namespace TearoomOne\Tests;

use Kirby\Form\Field;

/**
 * The generate button of the site-level mk-title/mk-description fields
 * must send 'site' as pageId, since Site::id() is null.
 */
class SiteFieldPageIdTest extends KirbyTestCase
{
    public static function fieldTypes(): array
    {
        return [['mk-title'], ['mk-description']];
    }

    /**
     * @dataProvider fieldTypes
     */
    public function testSiteFieldSendsSiteAsPageId(string $type): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => 'Title: Test Site',
            'about/default.txt' => 'Title: About',
        ]);

        Field::$types[$type] = require dirname(__DIR__) . '/fields/' . $type . '/index.php';

        $siteField = new Field($type, ['name' => 'metaTitle', 'model' => $kirby->site()]);
        $pageField = new Field($type, ['name' => 'metaTitle', 'model' => $kirby->page('about')]);

        $this->assertSame('site', $siteField->toArray()['validationSettings']['pageId']);
        $this->assertSame('about', $pageField->toArray()['validationSettings']['pageId']);
    }
}
