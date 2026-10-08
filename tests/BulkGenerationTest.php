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

    public function testWithoutPageIdsTheManagedPagesAreUsed(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site\n----\nMetatitle: Site title\n",
            'home/default.txt' => "Title: Home\n----\nText: " . self::TEXT . "\n----\nMetatitle: Home title\n",
            '_drafts/draft/default.txt' => "Title: Draft\n----\nText: " . self::TEXT . "\n",
            'hidden/secret.txt' => "Title: Hidden\n----\nText: " . self::TEXT . "\n",
        ], ['tearoom1.meta-kit' => [
            'api.key' => 'test-key',
            'api.model' => 'test-model',
            'api.endpoint' => self::$baseUrl . '/description',
            'excludeTemplates' => ['secret'],
        ]]);

        $result = MetaKitController::generateAllFields(generateTitle: true);

        // Only the draft was missing a title; the excluded page is left alone
        $this->assertSame(1, $result['generated']);
        $this->assertTrue($kirby->page('draft')->metaTitle()->isNotEmpty());
        $this->assertTrue($kirby->page('hidden')->metaTitle()->isEmpty());
    }

    public function testBulkEditDataIncludesDraftsButNotExcludedPages(): void
    {
        $this->makeKirby([
            'site.txt' => 'Title: Site',
            '_drafts/draft/default.txt' => 'Title: Draft',
            'hidden/secret.txt' => 'Title: Hidden',
        ], ['tearoom1.meta-kit' => ['excludeTemplates' => ['secret']]]);

        $_GET['pageIds'] = 'draft,hidden';
        try {
            $result = MetaKitController::getPagesWithContent();
        } finally {
            unset($_GET['pageIds']);
        }

        $this->assertSame(['draft'], array_column($result['data'], 'id'));
    }
}
