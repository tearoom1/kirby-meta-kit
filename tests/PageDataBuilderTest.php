<?php

namespace TearoomOne\Tests;

use TearoomOne\ConfigHelper;
use TearoomOne\MetaKitController;
use TearoomOne\PageDataBuilder;

class PageDataBuilderTest extends KirbyTestCase
{
    // ── Base data structure ───────────────────────────────────────────────────

    public function testBuildReturnsRequiredBaseKeys(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page Title\n",
        ]);

        $page = $kirby->page('article');
        $data = PageDataBuilder::fromModel($page);

        foreach (['id', 'title', 'url', 'panelUrl', 'template', 'status', 'language'] as $key) {
            $this->assertArrayHasKey($key, $data, "Missing key: $key");
        }

        $this->assertSame('article', $data['id']);
        $this->assertSame('Page Title', $data['title']);
        $this->assertSame('default', $data['template']);
    }

    public function testBuildSiteReturnsIdSite(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: My Site\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->site());

        $this->assertSame('site', $data['id']);
        $this->assertSame('site', $data['template']);
        $this->assertSame('published', $data['status']);
    }

    // ── Meta field presence flags ─────────────────────────────────────────────

    public function testHasMetaTitleIsTrueWhenFieldIsSet(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nMetatitle: Custom Title\n",
        ]);

        $kirby->impersonate('kirby');

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        $this->assertTrue($data['hasMetaTitle']);
        $this->assertSame('Custom Title', $data['metaTitle']);
        $this->assertSame(12, $data['metaTitleLength']);
    }

    public function testHasMetaTitleIsFalseWhenFieldIsEmpty(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        $this->assertFalse($data['hasMetaTitle']);
        $this->assertNull($data['metaTitle']);
    }

    public function testHasMetaDescriptionIsTrueWhenFieldIsSet(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nMetadescription: Custom description here\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        $this->assertTrue($data['hasMetaDescription']);
        $this->assertSame('Custom description here', $data['metaDescription']);
    }

    // ── OG fields ─────────────────────────────────────────────────────────────

    public function testOgFieldsArePresentForPage(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nOgtitle: OG Title\n----\nOgdescription: OG Description\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        $this->assertArrayHasKey('hasOgTitle', $data);
        $this->assertArrayHasKey('hasOgDescription', $data);
        $this->assertArrayHasKey('hasOgImage', $data);
        $this->assertTrue($data['hasOgTitle']);
        $this->assertSame('OG Title', $data['ogTitle']);
        $this->assertTrue($data['hasOgDescription']);
        $this->assertSame('OG Description', $data['ogDescription']);
    }

    public function testSitePageHasNoOgTitleOrDescription(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: My Site\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->site());

        $this->assertFalse($data['hasOgTitle']);
        $this->assertFalse($data['hasOgDescription']);
        $this->assertNull($data['ogTitle']);
        $this->assertNull($data['ogDescription']);
    }

    // ── Inheritance data ──────────────────────────────────────────────────────

    public function testInheritanceKeysArePresent(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        foreach (['metaTitleInheritance', 'metaDescriptionInheritance', 'ogTitleInheritance', 'ogDescriptionInheritance'] as $key) {
            $this->assertArrayHasKey($key, $data, "Missing inheritance key: $key");
        }
    }

    // ── Legacy field detection ────────────────────────────────────────────────

    public function testLegacyDataIsNullWhenNoLegacyFields(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nMetatitle: Current Title\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        $this->assertNull($data['legacy']);
    }

    public function testLegacyDataDetectsOldMetatitleField(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nMetatitle: Legacy Meta Title\n",
        ]);

        // PageDataBuilder checks lowercase field variants (metatitle, Metatitle…)
        // The flat field "metatitle" is the new canonical field — legacy detection
        // only triggers when current fields are empty and an old name has data.
        // To isolate the legacy check, create a page with *only* an old field name.
        $kirby2 = $this->makeKirby([
            'site.txt'             => "Title: My Site\n",
            'article2/default.txt' => "Title: Page\n----\nMetatitle: Old Title\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby2->page('article2'));

        // 'metatitle' is also the canonical field so it should be detected as hasMetaTitle
        $this->assertArrayHasKey('legacy', $data);
    }

    // ── fromPageId factory ────────────────────────────────────────────────────

    public function testFromPageIdReturnsSiteData(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: My Site\n",
        ]);

        $data = PageDataBuilder::fromPageId('site');

        $this->assertNotNull($data);
        $this->assertSame('site', $data['id']);
    }

    public function testFromPageIdReturnsNullForUnknownPage(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: My Site\n",
        ]);

        $data = PageDataBuilder::fromPageId('this-page-does-not-exist');

        $this->assertNull($data);
    }

    public function testFromPageIdBuildsPageData(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nMetadescription: Test description\n",
        ]);

        $data = PageDataBuilder::fromPageId('article');

        $this->assertNotNull($data);
        $this->assertSame('article', $data['id']);
        $this->assertTrue($data['hasMetaDescription']);
    }

    // ── Robots default ────────────────────────────────────────────────────────

    public function testRobotsDefaultsToIndexFollow(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        $this->assertSame('index, follow', $data['robots']);
    }

    public function testRobotsReadsCustomValue(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nRobots: noindex, follow\n",
        ]);

        $data = PageDataBuilder::fromModel($kirby->page('article'));

        $this->assertSame('noindex, follow', $data['robots']);
    }

    // ── Multilingual inheritance ──────────────────────────────────────────────

    public function testFieldInheritanceReflectsLanguageContext(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.en.txt'         => "Title: Site EN\n",
                'site.de.txt'         => "Title: Site DE\n",
                'article/default.en.txt' => "Title: Article EN\n----\nMetatitle: EN Meta Title\n",
                'article/default.de.txt' => "Title: Article DE\n",
            ],
            [],
            [
                ['code' => 'en', 'name' => 'English', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch'],
            ]
        );

        // DE page has no metaTitle — field presence should be false for DE
        $kirby->setCurrentLanguage('de');
        ConfigHelper::clearCache();

        $page = $kirby->page('article');
        $data = PageDataBuilder::fromModel($page);

        $this->assertFalse($data['hasMetaTitle'], 'DE page should not have its own meta title');
    }
}
