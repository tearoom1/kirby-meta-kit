<?php

namespace TearoomOne\Tests;

use TearoomOne\ConfigHelper;
use TearoomOne\MetaKit;

class SeoSnippetAndPanelViewsTest extends KirbyTestCase
{
    protected function setUp(): void
    {
        $this->resetPluginCaches();
    }

    public function testSeoSnippetRendersMetaOgAndSchemaByDefault(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site Title\n----\nMetatitle: Site Meta\n----\nMetadescription: Site description\n----\nAppendsitename: true\n----\nAppendsitenameto: meta,og\n----\nTitleseparator: |\n----\nRobots: index, follow\n",
            'article/default.txt' => "Title: Article\n----\nMetatitle: Custom Meta Title\n----\nMetadescription: Custom Meta Description\n----\nCanonicalurl: https://example.test/custom-canonical\n----\nRobots: noindex, follow\n",
        ]);

        $this->resetPluginCaches();
        $html = $this->renderSeoSnippet($kirby->page('article'));

        $this->assertStringContainsString('<title>Custom Meta Title | Site Meta</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Custom Meta Description">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://example.test/custom-canonical">', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex, follow">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Custom Meta Title | Site Meta">', $html);
        $this->assertStringContainsString('<meta name="twitter:title" content="Custom Meta Title | Site Meta">', $html);
        $this->assertStringContainsString('<script type="application/ld+json">', $html);
    }

    public function testSeoSnippetAlwaysRendersFullOutput(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site Title\n----\nMetatitle: Site Meta\n----\nMetadescription: Site description\n----\nAppendsitename: true\n----\nAppendsitenameto: meta,og\n----\nTitleseparator: |\n",
            'article/default.txt' => "Title: Article\n----\nMetatitle: This is a deliberately long custom meta title for testing\n----\nMetadescription: This is a deliberately long custom meta description that should render in full in the test environment.\n----\nOgtitle: This is a deliberately long Open Graph title for testing\n----\nOgdescription: This is a deliberately long Open Graph description that should render in full in the test environment.\n",
        ]);

        $this->resetPluginCaches();
        $html = $this->renderSeoSnippet($kirby->page('article'));

        $this->assertStringContainsString('This is a deliberately long custom meta title for testing | Site Meta', $html);
        $this->assertStringContainsString('This is a deliberately long custom meta description that should render in full in the test environment.', $html);
        $this->assertStringContainsString('This is a deliberately long Open Graph title for testing | Site Meta', $html);
        $this->assertStringContainsString('This is a deliberately long Open Graph description that should render in full in the test environment.', $html);
        $this->assertStringNotContainsString('...', $html);
    }

    public function testSeoSnippetRespectsFeatureToggles(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.txt' => "Title: Site Title\n----\nMetaTitle: Site Meta\n",
                'article/default.txt' => "Title: Article\n----\nMetatitle: Custom Meta Title\n----\nMetadescription: Custom Meta Description\n",
            ],
            [
                'tearoom1.meta-kit.meta.enabled' => false,
                'tearoom1.meta-kit.opengraph.enabled' => false,
                'tearoom1.meta-kit.schema.enabled' => false,
            ]
        );

        $this->resetPluginCaches();
        $html = $this->renderSeoSnippet($kirby->page('article'));

        $this->assertStringNotContainsString('<title>', $html);
        $this->assertStringNotContainsString('property="og:title"', $html);
        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    public function testSeoSnippetRendersAlternateLinksInMultilang(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.en.txt' => "Title: Site EN\n----\nMetatitle: Site EN\n----\nAppendsitename: false\n",
                'site.de.txt' => "Title: Site DE\n----\nMetatitle: Site DE\n----\nAppendsitename: false\n",
                'article/default.en.txt' => "Title: Article EN\n----\nMetatitle: Meta EN\n----\nMetadescription: Description EN\n",
                'article/default.de.txt' => "Title: Article DE\n----\nMetatitle: Meta DE\n----\nMetadescription: Description DE\n",
            ],
            [],
            [
                ['code' => 'en', 'name' => 'English', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch'],
            ]
        );

        $kirby->setCurrentLanguage('en');
        $this->resetPluginCaches();
        $html = $this->renderSeoSnippet($kirby->page('article'));

        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('hreflang="de"', $html);
        $this->assertStringContainsString('hreflang="x-default"', $html);
    }

    public function testMetaKitAreaViewReturnsExpectedProps(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.en.txt' => "Title: Site EN\n----\nMetatitle: Site EN\n",
                'site.de.txt' => "Title: Site DE\n----\nMetatitle: Site DE\n",
                'article/default.en.txt' => "Title: Article EN\n----\nMetatitle: Meta EN\n----\nMetadescription: Description EN\n",
                'article/default.de.txt' => "Title: Article DE\n----\nMetatitle: Meta DE\n----\nMetadescription: Description DE\n",
            ],
            [],
            [
                ['code' => 'en', 'name' => 'English', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch'],
            ]
        );

        $_GET['language'] = 'de';
        $area = require __DIR__ . '/../src/areas/meta-kit.php';
        $view = $area['views'][0];
        $this->resetPluginCaches();
        $result = $view['action']();
        unset($_GET['language']);

        $this->assertSame('meta-kit-view', $result['component']);
        $this->assertSame('Meta Kit', $result['title']);
        $this->assertArrayHasKey('props', $result);
        $this->assertArrayHasKey('pages', $result['props']);
        $this->assertArrayHasKey('siteSettings', $result['props']);
        $this->assertArrayHasKey('languages', $result['props']);
        $this->assertSame('de', $result['props']['language']);
        $this->assertSame('de', $kirby->language()->code());
    }

    public function testSeoPreviewSectionComputedMetaUsesHelperOutput(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site Title\n----\nMetatitle: Site Meta\n----\nMetadescription: Site Description\n----\nAppendsitename: true\n----\nAppendsitenameto: meta,og\n----\nTitleseparator: |\n",
            'article/default.txt' => "Title: Article\n----\nMetatitle: Fish &amp; Chips\n----\nMetadescription: Page Description\n",
        ]);

        $this->resetPluginCaches();
        $sectionConfig = require __DIR__ . '/../sections/preview.php';
        $computeMeta = $sectionConfig['computed']['meta'];
        $page = $kirby->page('article');

        $fakeSection = new class($page)
        {
            public function __construct(private $model)
            {
            }

            public function model()
            {
                return $this->model;
            }
        };

        $meta = $computeMeta->call($fakeSection);

        $this->assertSame($page->url(), $meta['url']);
        $this->assertSame('Fish & Chips | Site Meta', $meta['title']);
        $this->assertSame('Page Description', $meta['description']);
        $this->assertSame('Fish & Chips | Site Meta', $meta['ogTitle']);
        $this->assertSame('Page Description', $meta['ogDescription']);
    }

    private function renderSeoSnippet($page): string
    {
        $site = site();
        $snippetPath = __DIR__ . '/../snippets/seo.php';

        ob_start();
        include $snippetPath;
        return ob_get_clean();
    }

    private function resetPluginCaches(): void
    {
        ConfigHelper::clearCache();
        $reflection = new \ReflectionClass(MetaKit::class);
        $property = $reflection->getProperty('aiEnabledCache');
        $property->setValue(null, null);
    }
}
