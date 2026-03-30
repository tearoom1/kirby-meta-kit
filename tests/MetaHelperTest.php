<?php

namespace TearoomOne\Tests;

use TearoomOne\ConfigHelper;
use TearoomOne\MetaHelper;

class MetaHelperTest extends KirbyTestCase
{
    // ── buildTitle ────────────────────────────────────────────────────────────

    public function testBuildTitleUsesMetaTitleWhenSet(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'             => "Title: My Site\n----\nMetatitle: Site SEO\n----\nAppendsitename: false\n",
            'article/default.txt'  => "Title: Page Title\n----\nMetatitle: Custom Meta Title\n",
        ]);

        ConfigHelper::clearCache();
        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('Custom Meta Title', MetaHelper::buildTitle($page, $site, 'meta'));
    }

    public function testBuildTitleFallsBackToPageTitleWhenNoMetaTitle(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n----\nAppendsitename: false\n",
            'article/default.txt' => "Title: Page Title\n",
        ]);

        ConfigHelper::clearCache();
        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('Page Title', MetaHelper::buildTitle($page, $site, 'meta'));
    }

    public function testBuildTitleAppendsSiteNameWhenEnabled(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n----\nMetatitle: Brand\n----\nAppendsitename: true\n----\nAppendsitenameto: meta,og\n----\nTitleseparator: |\n",
            'article/default.txt' => "Title: Page Title\n----\nMetatitle: Article Title\n",
        ]);

        ConfigHelper::clearCache();
        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('Article Title | Brand', MetaHelper::buildTitle($page, $site, 'meta'));
    }

    public function testBuildTitleDoesNotAppendSiteNameForOgWhenNotConfigured(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n----\nMetatitle: Brand\n----\nAppendsitename: true\n----\nAppendsitenameto: meta\n----\nTitleseparator: |\n",
            'article/default.txt' => "Title: Page Title\n----\nMetatitle: Article Title\n",
        ]);

        ConfigHelper::clearCache();
        $page = $kirby->page('article');
        $site = $kirby->site();

        // OG is not in the appendSiteNameTo list
        $this->assertSame('Article Title', MetaHelper::buildTitle($page, $site, 'og'));
    }

    public function testBuildTitleUsesOgTitleForOgType(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n----\nAppendsitename: false\n",
            'article/default.txt' => "Title: Page Title\n----\nMetatitle: Meta Title\n----\nOgtitle: OG Specific Title\n",
        ]);

        ConfigHelper::clearCache();
        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('OG Specific Title', MetaHelper::buildTitle($page, $site, 'og'));
    }

    // ── buildDescription ──────────────────────────────────────────────────────

    public function testBuildDescriptionUsesPageMetaDescription(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n----\nMetadescription: Site description\n",
            'article/default.txt' => "Title: Page\n----\nMetadescription: Page specific description\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('Page specific description', MetaHelper::buildDescription($page, $site));
    }

    public function testBuildDescriptionFallsBackToSiteDescription(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n----\nMetadescription: Site fallback description\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('Site fallback description', MetaHelper::buildDescription($page, $site));
    }

    public function testBuildDescriptionReturnsEmptyWhenNothingSet(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('', MetaHelper::buildDescription($page, $site));
    }

    // ── buildOgDescription ────────────────────────────────────────────────────

    public function testBuildOgDescriptionUsesOgSpecificField(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nMetadescription: Meta desc\n----\nOgdescription: OG specific desc\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('OG specific desc', MetaHelper::buildOgDescription($page, $site));
    }

    public function testBuildOgDescriptionFallsBackToMetaDescription(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n----\nMetadescription: Meta desc\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('Meta desc', MetaHelper::buildOgDescription($page, $site));
    }

    public function testBuildOgDescriptionFallsBackToSiteDescription(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n----\nMetadescription: Site desc\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('Site desc', MetaHelper::buildOgDescription($page, $site));
    }

    public function testBuildOgDescriptionUsesProvidedMetaDescriptionArgument(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        // No fields set, but a $metaDescription argument is passed
        $this->assertSame('Passed description', MetaHelper::buildOgDescription($page, $site, 'Passed description'));
    }

    public function testBuildOgDescriptionReturnsEmptyWhenNothingAvailable(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $page = $kirby->page('article');
        $site = $kirby->site();

        $this->assertSame('', MetaHelper::buildOgDescription($page, $site));
    }

    // ── getSeoData ────────────────────────────────────────────────────────────

    public function testGetSeoDataReturnsNullForEmptyField(): void
    {
        $kirby = $this->makeKirby([
            'site.txt'            => "Title: My Site\n",
            'article/default.txt' => "Title: Page\n",
        ]);

        $page = $kirby->page('article');
        $result = MetaHelper::getSeoData($page->metaTitle());
        $this->assertNull($result);
    }
}
