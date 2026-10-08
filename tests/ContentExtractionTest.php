<?php

namespace TearoomOne\Tests;

use TearoomOne\MetaKitController;

class ContentExtractionTest extends KirbyTestCase
{
    private const TEXT = 'A sufficiently long paragraph about tea that easily passes the minimum content length.';

    private function extract($page): string
    {
        $method = (new \ReflectionClass(MetaKitController::class))->getMethod('extractPageContent');
        return $method->invoke(null, $page);
    }

    private function blueprints(): array
    {
        return ['blueprints' => [
            'pages/article' => ['fields' => [
                'intro' => ['type' => 'textarea'],
                'website' => ['type' => 'url'],
            ]],
            // Like a home page that only lists other pages
            'pages/listing' => ['sections' => [
                'children' => ['type' => 'pages'],
            ]],
        ]];
    }

    private function aiOptions(array $extra = []): array
    {
        return ['tearoom1.meta-kit' => [
            'api.key' => 'test-key',
            'api.model' => 'test-model',
            // Unreachable: generation must stop before any API call
            'api.endpoint' => 'http://127.0.0.1:9/never-called',
            ...$extra,
        ]];
    }

    public function testOnlyBlueprintFieldsAreUsed(): void
    {
        $kirby = $this->makeKirby([
            'post/article.txt' => "Title: Post\n----\nIntro: " . self::TEXT . "\n----\nWebsite: https://example.com/a-very-long-url-that-is-not-page-text\n----\nLayout: Leftover retreat text from an earlier blueprint version of this page\n",
        ], extraConfig: $this->blueprints());

        $content = $this->extract($kirby->page('post'));

        $this->assertStringContainsString('paragraph about tea', $content);
        $this->assertStringNotContainsString('retreat', $content);
        $this->assertStringNotContainsString('example.com', $content);
    }

    public function testPagesWithoutOwnBlueprintStillUseAllFields(): void
    {
        $kirby = $this->makeKirby([
            'post/default.txt' => "Title: Post\n----\nText: " . self::TEXT . "\n",
        ]);

        $this->assertStringContainsString('paragraph about tea', $this->extract($kirby->page('post')));
    }

    public function testGenerationStopsWhenPageHasTooLittleText(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => 'Title: Site',
            'list/listing.txt' => "Title: List\n----\nLayout: Leftover text that must not be used for generating anything at all\n",
        ], $this->aiOptions(), extraConfig: $this->blueprints());

        $this->assertFalse(MetaKitController::hasEnoughContent($kirby->page('list')));

        $result = MetaKitController::generateField('list', 'metaDescription');

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Not enough text', $result['message']);
    }

    public function testSiteUsesHomePageContentForTheCheck(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => 'Title: Site',
            'home/listing.txt' => "Title: Home\n",
        ], $this->aiOptions(), extraConfig: $this->blueprints());

        $result = MetaKitController::generateField('site', 'metaTitle');

        $this->assertStringContainsString('Not enough text', $result['message']);
    }

    public function testMinimumContentLengthIsConfigurable(): void
    {
        $kirby = $this->makeKirby([
            'post/article.txt' => "Title: Post\n----\nIntro: " . self::TEXT . "\n",
        ], ['tearoom1.meta-kit' => ['ai.minContentLength' => 500]], extraConfig: $this->blueprints());

        $this->assertFalse(MetaKitController::hasEnoughContent($kirby->page('post')));
    }
}
