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

    public function testSitemapPathRedirectsToSitemapXml(): void
    {
        $kirby = $this->makeKirby(
            ['site.txt' => "Title: Test Site\n"],
            [],
            null,
            ['routes' => require __DIR__ . '/../src/routes/routes.php']
        );

        $response = $kirby->call('sitemap');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(301, $response->code());
        $this->assertStringEndsWith('/sitemap.xml', $response->header('Location'));
    }

    public function testSitemapPathFallsThroughWhenSitemapIsDisabled(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.txt' => "Title: Test Site\n",
                'sitemap/default.txt' => "Title: Sitemap Page\n",
            ],
            ['tearoom1.meta-kit.sitemap.enabled' => false],
            null,
            ['routes' => require __DIR__ . '/../src/routes/routes.php']
        );

        $result = $kirby->call('sitemap');

        $this->assertNotInstanceOf(Response::class, $result);
        $this->assertSame('sitemap', $result?->id());
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

    public function testSitemapLastmodFallsBackToDefaultLanguageAndIsOmittedWithoutContentFile(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.de.txt' => "Title: Site DE\n",
                'site.en.txt' => "Title: Site EN\n",
                '1_listed/default.de.txt' => "Title: Listed DE\n",
                '2_bare/photo.jpg' => '',
            ],
            [],
            [
                ['code' => 'de', 'name' => 'Deutsch', 'default' => true],
                ['code' => 'en', 'name' => 'English'],
            ]
        );

        $modified = mktime(12, 0, 0, 1, 15, 2024);
        touch($kirby->root('content') . '/1_listed/default.de.txt', $modified);
        clearstatcache();

        $entries = (new Sitemap($kirby))->generate();
        $byUrl = array_column($entries, 'lastmod', 'url');

        // Missing English file: the German file's time, never "now"
        $this->assertSame(date('c', $modified), $byUrl[$kirby->page('listed')->url('de')]);
        $this->assertSame(date('c', $modified), $byUrl[$kirby->page('listed')->url('en')]);
        // No content file at all: no lastmod
        $this->assertNull($byUrl[$kirby->page('bare')->url('de')]);
        $this->assertNull($byUrl[$kirby->page('bare')->url('en')]);

        $xml = (new Sitemap($kirby))->toXml();
        $this->assertSame(2, substr_count($xml, '<lastmod>'));
        $this->assertStringNotContainsString('<lastmod></lastmod>', $xml);
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
        $kirby = $this->makeKirbyWithHooks();

        $first = $this->renderSitemap();
        $this->assertStringContainsString('/about</loc>', $first);

        // A page added outside of Kirby's API doesn't flush the cache…
        $this->addPageOutsideKirby($kirby, '2_contact');
        $this->assertSame($first, $this->renderSitemap());

        // …the page/file/site hooks do
        $kirby->trigger('page.update:after', ['newPage' => $kirby->page('about'), 'oldPage' => $kirby->page('about')]);
        $this->assertStringContainsString('/contact</loc>', $this->renderSitemap());
    }

    /**
     * A Kirby instance with the plugin's hooks (minus the settings
     * initialization, which would write to site.txt) and the sitemap cache
     */
    private function makeKirbyWithHooks(): \Kirby\Cms\App
    {
        $hooks = require __DIR__ . '/../src/hooks.php';
        unset($hooks['system.loadPlugins:after']);

        return $this->makeKirby(
            ['site.txt' => 'Title: Site', '1_about/default.txt' => 'Title: About'],
            ['cache' => ['tearoom1.meta-kit.sitemap' => true]],
            extraConfig: ['hooks' => $hooks]
        );
    }

    public function testPageRenderDoesNotFlushTheSitemapCache(): void
    {
        $kirby = $this->makeKirbyWithHooks();
        $first = $this->renderSitemap();
        $this->addPageOutsideKirby($kirby, '2_contact');

        // Every page view fires page.render:after; it used to match the
        // former "page.*:after" hook and flush the cache each time
        $page = $kirby->page('about');
        $kirby->trigger('page.render:after', ['contentType' => 'html', 'data' => [], 'html' => '', 'page' => $page]);

        $this->assertSame($first, $this->renderSitemap());
    }

    public function testContentChangesFlushTheSitemapCache(): void
    {
        $hooks = require __DIR__ . '/../src/hooks.php';
        $this->assertArrayNotHasKey('page.*:after', $hooks);
        $this->assertArrayNotHasKey('file.*:after', $hooks);
        $this->assertArrayNotHasKey('page.render:after', $hooks);

        $kirby = $this->makeKirbyWithHooks();
        $this->renderSitemap();
        $this->addPageOutsideKirby($kirby, '2_contact');

        // A real update through Kirby's API flushes the cache
        $kirby->page('about')->update(['title' => 'About us']);

        $this->assertStringContainsString('/contact</loc>', $this->renderSitemap());
    }

    public function testEveryFlushEventIsRegistered(): void
    {
        $hooks = require __DIR__ . '/../src/hooks.php';

        foreach (\TearoomOne\Sitemap::FLUSH_EVENTS as $event) {
            $this->assertArrayHasKey($event . ':after', $hooks, $event);
        }

        // Events with their own handler keep it and flush as well
        $this->assertIsArray($hooks['page.update:after']);
        $this->assertCount(2, $hooks['page.update:after']);
    }

    public function testFlushIsSafeWhenTheCacheFolderIsMissingOrBeingRemoved(): void
    {
        $kirby = $this->makeKirby(
            ['site.txt' => 'Title: Site', '1_about/default.txt' => 'Title: About'],
            ['cache' => ['tearoom1.meta-kit.sitemap' => true]]
        );

        // Turn warnings into exceptions, like Kirby's error handler does on
        // a frontend request
        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            // Nothing cached yet: the folder doesn't exist
            \TearoomOne\Sitemap::flushCache();

            // Another request removes the folder while this one flushes it:
            // the file system calls warn and the cache driver throws
            $caches = new \ReflectionProperty($kirby, 'caches');
            $caches->setValue($kirby, ['tearoom1.meta-kit.sitemap' => new RacingFlushCache()]);
            $this->assertInstanceOf(RacingFlushCache::class, $kirby->cache('tearoom1.meta-kit.sitemap'));
            \TearoomOne\Sitemap::flushCache();
            $this->assertTrue(RacingFlushCache::$flushed);

            // The handler in place before the flush is restored
            $this->expectException(\ErrorException::class);
            trigger_error('still converted', E_USER_WARNING);
        } finally {
            restore_error_handler();
        }
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

/**
 * A cache whose flush fails the way a concurrent removal of the same folder
 * does: scandir() warns about the vanished folder, then an exception follows
 */
class RacingFlushCache extends \Kirby\Cache\MemoryCache
{
    public static bool $flushed = false;

    public function flush(): bool
    {
        static::$flushed = true;
        scandir(sys_get_temp_dir() . '/meta-kit-vanished-' . uniqid());
        throw new \RuntimeException('Directory vanished');
    }
}
