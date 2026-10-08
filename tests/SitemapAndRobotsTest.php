<?php

namespace TearoomOne\Tests;

use Kirby\Http\Response;
use TearoomOne\Sitemap;

class SitemapAndRobotsTest extends KirbyTestCase
{
    public function testRobotsRouteReturnsBasicFallbackWhenNoSettingsExist(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: Test Site\n",
        ]);

        $route = require __DIR__ . '/../src/routes/robots.txt.php';
        $response = $route();
        $content = $response->body();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->code());
        $this->assertSame('text/plain', $response->type());
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /panel/', $content);
        $this->assertStringContainsString('Sitemap:', $content);
    }

    public function testRobotsRouteUsesPanelSettingsAndRespectsIncludeSitemapFlag(): void
    {
        $robotsBlocks = $this->makeBlocksJson('mk-robots', [
            'enabled' => true,
            'defaultRules' => false,
            'includeSitemap' => false,
            'blockBadBots' => false,
            'customRules' => [
                [
                    'useragent' => 'Googlebot',
                    'customuseragent' => null,
                    'allowpaths' => '/public',
                    'disallowpaths' => '/private',
                    'crawldelay' => '3',
                ]
            ],
            'customDirectives' => "Host: example.test",
        ]);

        $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nMetakitrobots: {$robotsBlocks}\n",
        ], [
            'tearoom1.meta-kit.robots' => [
                'includeSitemap' => false,
            ],
        ]);

        $route = require __DIR__ . '/../src/routes/robots.txt.php';
        $response = $route();
        $content = $response->body();

        $this->assertStringContainsString('User-agent: Googlebot', $content);
        $this->assertStringContainsString('Allow: /public', $content);
        $this->assertStringContainsString('Disallow: /private', $content);
        $this->assertStringContainsString('Crawl-delay: 3', $content);
        $this->assertStringContainsString('Host: example.test', $content);
        $this->assertStringNotContainsString('Sitemap:', $content);
    }

    public function testSitemapRouteReturns404WhenDisabled(): void
    {
        $this->makeKirby(
            [
                'site.txt' => "Title: Test Site\n",
            ],
            [
                'tearoom1.meta-kit.sitemap.enabled' => false,
            ]
        );

        $route = require __DIR__ . '/../src/routes/sitemap.php';
        $response = $route();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->code());
        $this->assertStringContainsString('Sitemap is disabled', $response->body());
    }

    public function testSitemapGenerateExcludesUnlistedAndNoindexByDefault(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Test Site\n",
            'listed/default.txt' => "Title: Listed Page\n",
            'noindex/default.txt' => "Title: Noindex Page\n----\nRobots: noindex, follow\n",
        ], [
            'tearoom1.meta-kit' => [
                'sitemap.includeUnlisted' => true,
            ],
        ]);

        $entries = (new Sitemap($kirby))->generate();
        $urls = array_map(fn ($entry) => $entry['url'], $entries);

        $this->assertCount(1, $entries);
        $this->assertStringContainsString('/listed', $urls[0]);
    }

    public function testSitemapRouteIncludesLanguageAlternatesInMultilang(): void
    {
        $this->makeKirby(
            [
                'site.en.txt' => "Title: Site EN\n",
                'site.de.txt' => "Title: Site DE\n",
                '1_listed/default.en.txt' => "Title: Listed EN\n",
                '1_listed/default.de.txt' => "Title: Listed DE\n",
            ],
            [
                'tearoom1.meta-kit' => [
                    'sitemap.includeUnlisted' => true,
                ],
            ],
            [
                ['code' => 'en', 'name' => 'English', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch'],
            ]
        );

        $route = require __DIR__ . '/../src/routes/sitemap.php';
        $response = $route();
        $xml = $response->body();

        $this->assertSame(200, $response->code());
        $this->assertSame('application/xml', $response->type());
        $this->assertStringContainsString('xmlns:xhtml=', $xml);
        $this->assertStringContainsString('xhtml:link rel="alternate" hreflang="en"', $xml);
        $this->assertStringContainsString('xhtml:link rel="alternate" hreflang="de"', $xml);
        // x-default should point to default language for unmatched visitors
        $this->assertStringContainsString('xhtml:link rel="alternate" hreflang="x-default"', $xml);
        // Each language version of the page must appear as its own <url> entry
        $this->assertSame(2, substr_count($xml, '<url>'), 'Expected one <url> entry per language');
    }

    public function testSitemapGenerateMultilangCreatesOneEntryPerLanguage(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.en.txt' => "Title: Site EN\n",
                'site.de.txt' => "Title: Site DE\n",
                '1_listed/default.en.txt' => "Title: Listed EN\n",
                '1_listed/default.de.txt' => "Title: Listed DE\n",
            ],
            [],
            [
                ['code' => 'en', 'name' => 'English', 'default' => true],
                ['code' => 'de', 'name' => 'Deutsch'],
            ]
        );

        $entries = (new Sitemap($kirby))->generate();

        // 1 page × 2 languages = 2 entries
        $this->assertCount(2, $entries);

        $urls = array_column($entries, 'url');
        $this->assertTrue(
            count(array_filter($urls, fn ($u) => str_contains($u, '/en/'))) > 0,
            'Expected an English URL entry'
        );
        $this->assertTrue(
            count(array_filter($urls, fn ($u) => str_contains($u, '/de/'))) > 0,
            'Expected a German URL entry'
        );

        // Every entry must carry alternates including x-default
        foreach ($entries as $entry) {
            $this->assertNotEmpty($entry['alternates']);
            $hreflangs = array_column($entry['alternates'], 'lang');
            $this->assertContains('en', $hreflangs);
            $this->assertContains('de', $hreflangs);
            $this->assertContains('x-default', $hreflangs);
        }
    }

    public function testRobotsTogglesStoredAsStringsAreRespected(): void
    {
        $robotsBlocks = $this->makeBlocksJson('mk-robots', [
            'enabled' => 'true',
            'defaultRules' => 'true',
            'includeSitemap' => 'false',
            'blockBadBots' => 'false',
        ]);
        $this->makeKirby(['site.txt' => "Title: Test Site\n----\nMetakitrobots: {$robotsBlocks}\n"]);

        $content = (new \TearoomOne\Robots(kirby()))->generate();

        $this->assertStringNotContainsString('Sitemap:', $content);
        $this->assertStringNotContainsString('AhrefsBot', $content);
        $this->assertStringContainsString('User-agent: *', $content);
    }

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private function renderSitemap(): string
    {
        $route = require __DIR__ . '/../src/routes/sitemap.php';
        return $route()->body();
    }

    public function testSitemapListsPageImages(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => 'Title: Site',
            '1_about/default.txt' => 'Title: About',
        ]);
        file_put_contents($kirby->root('content') . '/1_about/tea.png', base64_decode(self::PNG));

        $xml = $this->renderSitemap();

        $this->assertStringContainsString('xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"', $xml);
        $this->assertMatchesRegularExpression('~<image:image><image:loc>[^<]*/tea\.png</image:loc></image:image>~', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap must be valid XML');
    }

    public function testSitemapImagesCanBeDisabled(): void
    {
        $kirby = $this->makeKirby(
            ['site.txt' => 'Title: Site', '1_about/default.txt' => 'Title: About'],
            ['tearoom1.meta-kit' => ['sitemap.images' => false]]
        );
        file_put_contents($kirby->root('content') . '/1_about/tea.png', base64_decode(self::PNG));

        $xml = $this->renderSitemap();

        $this->assertStringContainsString('/about</loc>', $xml);
        $this->assertStringNotContainsString('image:', $xml);
    }

    private function addPageOutsideKirby($kirby, string $folder): void
    {
        mkdir($kirby->root('content') . '/' . $folder);
        file_put_contents($kirby->root('content') . '/' . $folder . '/default.txt', 'Title: Contact');
        $kirby->site()->purge();
    }

    public function testSitemapIsCachedUntilFlushed(): void
    {
        // The plugin isn't registered in these tests, so the cache is
        // configured by its core key (PageMethodsTest covers the plugin setup)
        $kirby = $this->makeKirby(
            ['site.txt' => 'Title: Site', '1_about/default.txt' => 'Title: About'],
            ['cache' => ['tearoom1.meta-kit.sitemap' => true]]
        );

        $first = $this->renderSitemap();
        $this->assertStringContainsString('/about</loc>', $first);

        // A page added outside of Kirby's API doesn't flush the cache…
        $this->addPageOutsideKirby($kirby, '2_contact');
        $this->assertSame($first, $this->renderSitemap());

        // …the page/file/site hooks do
        $hooks = require __DIR__ . '/../src/hooks.php';
        $hooks['page.*:after']();
        $this->assertStringContainsString('/contact</loc>', $this->renderSitemap());
    }

    public function testSitemapCacheCanBeDisabled(): void
    {
        $kirby = $this->makeKirby(
            ['site.txt' => 'Title: Site', '1_about/default.txt' => 'Title: About'],
            ['cache' => ['tearoom1.meta-kit.sitemap' => true], 'tearoom1.meta-kit' => ['sitemap.cache' => false]]
        );

        $this->renderSitemap();
        $this->addPageOutsideKirby($kirby, '2_contact');

        $this->assertStringContainsString('/contact</loc>', $this->renderSitemap());
    }
}
