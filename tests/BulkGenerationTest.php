<?php

namespace TearoomOne\Tests;

use TearoomOne\MetaKitController;

class BulkGenerationTest extends KirbyTestCase
{
    use MockApiServer;

    private const TEXT = 'A sufficiently long paragraph about tea that easily passes the minimum content length.';

    private function makeSite(string $scenario = '/description')
    {
        return $this->makeKirby([
            'site.txt' => 'Title: Site',
            'home/default.txt' => "Title: Home\n----\nText: " . self::TEXT . "\n",
            'done/default.txt' => "Title: Done\n----\nText: " . self::TEXT . "\n----\nMetatitle: Already written\n",
            'todo/default.txt' => "Title: Todo\n----\nText: " . self::TEXT . "\n",
            'thin/default.txt' => "Title: Thin\n----\nText: Short\n",
        ], ['tearoom1.meta-kit' => [
            'api.key' => 'test-key',
            'api.model' => 'test-model',
            'api.endpoint' => self::$baseUrl . $scenario,
        ]]);
    }

    public function testGeneratesOnlyMissingFieldsAndCountsResults(): void
    {
        $kirby = $this->makeSite();

        $result = MetaKitController::generateAllFields(
            generateTitle: true,
            generateOgTitle: true,
            pageIds: ['site', 'done', 'todo', 'thin']
        );

        // site: title (no OG fields) · done: OG title · todo: both · thin: both fail
        $this->assertSame(4, $result['generated']);
        $this->assertSame(2, $result['failed']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame('Already written', $kirby->page('done')->metaTitle()->value());
        $this->assertTrue($kirby->page('todo')->metaTitle()->isNotEmpty());
        $this->assertTrue($kirby->page('todo')->ogTitle()->isNotEmpty());
        $this->assertSame(['thin', 'thin'], array_column($result['errors'], 'pageId'));
        $this->assertStringContainsString('meta titles, OG titles', $result['message']);
    }

    public function testPagesWithAllFieldsAreSkipped(): void
    {
        $this->makeSite();

        $result = MetaKitController::generateAllFields(generateTitle: true, pageIds: ['done']);

        $this->assertSame(['generated' => 0, 'skipped' => 1, 'failed' => 0], [
            'generated' => $result['generated'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
        ]);
    }
}
