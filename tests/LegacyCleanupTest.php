<?php

namespace TearoomOne\Tests;

use TearoomOne\LegacyCleanup;

class LegacyCleanupTest extends KirbyTestCase
{
    public function testDetectLegacyMetadataFindsSiteAndPageWhenLegacyFieldsExistEvenIfEmpty(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nMetatitle:\n----\n",
            'article/default.txt' => "Title: Article\n----\nMeta_description:\n----\n",
        ]);

        $result = LegacyCleanup::detectLegacyMetadata();

        $this->assertSame('success', $result['status']);
        $this->assertSame(2, $result['found']);

        $ids = array_column($result['pages'], 'id');
        $this->assertContains('site', $ids);
        $this->assertContains('article', $ids);
    }

    public function testCleanupLegacyFieldsRemovesLegacyKeysFromSiteAndPage(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nMetatitle: Old Site Title\n----\n",
            'article/default.txt' => "Title: Article\n----\nMetadescription: Old Description\n----\nMetatitle: Old Title\n----\n",
        ]);

        $siteResult = LegacyCleanup::cleanupLegacyFields('site');
        $pageResult = LegacyCleanup::cleanupLegacyFields('article');

        $this->assertSame('success', $siteResult['status']);
        $this->assertSame('success', $pageResult['status']);

        $this->assertFalse($kirby->site()->content()->has('metatitle'));
        $this->assertFalse($kirby->page('article')->content()->has('metadescription'));
        $this->assertFalse($kirby->page('article')->content()->has('metatitle'));
    }
}
