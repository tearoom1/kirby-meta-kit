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
                '01-listed/default.en.txt' => "Title: Listed EN\n",
                '01-listed/default.de.txt' => "Title: Listed DE\n",
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
    }
}
