<?php

namespace TearoomOne\Tests;

use TearoomOne\LlmsTxt;
use TearoomOne\Robots;

class LlmsTxtAndAiCrawlersTest extends KirbyTestCase
{
    private function robotsSite(array $settings, array $options = []): void
    {
        $blocks = $this->makeBlocksJson('mk-robots', ['enabled' => true, ...$settings]);
        $this->makeKirby(['site.txt' => "Title: Site\n----\nMetakitrobots: {$blocks}\n"], $options);
    }

    public function testAiTrainingCrawlersAreBlockedOnRequest(): void
    {
        $this->robotsSite(['blockAiCrawlers' => true]);

        $content = (new Robots(kirby()))->generate();

        $this->assertStringContainsString("User-agent: GPTBot\nDisallow: /", $content);
        $this->assertStringContainsString("User-agent: ClaudeBot\nDisallow: /", $content);
        $this->assertStringContainsString("User-agent: Google-Extended\nDisallow: /", $content);
        // Assistants that cite pages stay allowed
        $this->assertStringNotContainsString('OAI-SearchBot', $content);
        $this->assertStringNotContainsString('ChatGPT-User', $content);
    }

    public function testAiCrawlersAreAllowedByDefaultAndViaConfig(): void
    {
        $this->robotsSite([]);
        $this->assertStringNotContainsString('GPTBot', (new Robots(kirby()))->generate());

        $this->robotsSite([], ['tearoom1.meta-kit' => ['robots' => ['blockAiCrawlers' => true]]]);
        $this->assertStringContainsString('User-agent: GPTBot', (new Robots(kirby()))->generate());
    }

    private function route()
    {
        $route = require __DIR__ . '/../src/routes/llms.txt.php';
        return $route();
    }

    public function testLlmsTxtIsOffByDefault(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site']);

        $this->assertFalse(LlmsTxt::isEnabled());
        $this->assertFalse($this->route());
    }

    public function testLlmsTxtCanBeEnabledInThePanel(): void
    {
        $this->robotsSite(['llmsTxt' => true]);

        $this->assertTrue(LlmsTxt::isEnabled());
    }

    public function testLlmsTxtListsSitemapPagesGroupedBySection(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: Tea [Room]\n----\nMetadescription: A small tea room\n",
            '1_about/default.txt' => "Title: About\n----\nMetadescription: Who we are\n",
            '2_journal/default.txt' => "Title: Journal\n",
            '2_journal/1_sencha/default.txt' => "Title: Sencha\n----\nMetatitle: All about Sencha\n",
            '3_private/default.txt' => "Title: Private\n----\nRobots: noindex, nofollow\n",
        ], ['tearoom1.meta-kit' => ['llms.enabled' => true]]);

        $response = $this->route();
        $text = $response->body();

        $this->assertSame('text/markdown', $response->type());
        $this->assertStringStartsWith("# Tea (Room)\n\n> A small tea room\n\n## Pages\n\n- [About](", $text);
        $this->assertStringContainsString('/about): Who we are', $text);
        $this->assertMatchesRegularExpression('~## Journal\n\n- \[Journal\]\([^)]*/journal\)\n- \[All about Sencha\]\([^)]*/journal/sencha\)~', $text);
        // noindex pages are left out, like in the sitemap
        $this->assertStringNotContainsString('Private', $text);
        // Inherited site descriptions aren't repeated on every page
        $this->assertStringNotContainsString('Journal): A small tea room', $text);
    }
}
