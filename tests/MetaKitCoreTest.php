<?php

namespace TearoomOne\Tests;

use TearoomOne\ConfigHelper;
use TearoomOne\MetaKit;
use TearoomOne\MetaKitController;

class MetaKitCoreTest extends KirbyTestCase
{
    public function testConfigHelperCacheClear(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nAppendsitename: true\n----\nAppendsitenameto: meta,og\n----\nTitleseparator: |",
        ]);

        $kirby->impersonate('kirby');
        $kirby->site()->update(['metaTitle' => 'Initial Title']);
        ConfigHelper::clearCache();

        $settings = ConfigHelper::getSiteSettings();
        $this->assertEquals('Initial Title', $settings['siteMetaTitle']);

        $kirby->site()->update(['metaTitle' => 'Updated Title']);
        $settingsCached = ConfigHelper::getSiteSettings();
        $this->assertEquals('Updated Title', $settingsCached['siteMetaTitle']);

        ConfigHelper::clearCache();
        $settingsUpdated = ConfigHelper::getSiteSettings();
        $this->assertEquals('Updated Title', $settingsUpdated['siteMetaTitle']);
    }

    public function testIsAiEnabledWithConfig(): void
    {
        $this->resetAiEnabledCache();
        $this->makeKirby(
            ['site.txt' => "Title: Test Site"],
            [
                'tearoom1.meta-kit' => [
                    'api.key' => 'test-key',
                    'api.model' => 'test-model',
                ],
            ]
        );

        $this->assertTrue(MetaKit::isAiEnabled());
    }

    public function testIsAiEnabledReturnsFalseWithoutCredentials(): void
    {
        $this->resetAiEnabledCache();
        $this->makeKirby(
            ['site.txt' => "Title: Test Site\n----\nMetakitopenrouter: "],
            [
                'tearoom1.meta-kit.api.key' => '',
                'tearoom1.meta-kit.api.model' => '',
            ]
        );

        // Guard against static state from earlier tests
        ConfigHelper::clearCache();
        $this->resetAiEnabledCache();
        $this->assertFalse(MetaKit::isAiEnabled());
    }

    public function testIsAiEnabledWithSiteSettings(): void
    {
        $this->resetAiEnabledCache();
        $this->makeKirby(
            [
                'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    apiKey: site-key\n    model: site-model\n    temperature: 0.7\n",
            ],
            [
                'tearoom1.meta-kit.api.key' => null,
                'tearoom1.meta-kit.api.model' => null,
            ]
        );

        $this->assertTrue(MetaKit::isAiEnabled());
    }

    public function testGetPagesWithContentDefaultIncludesSite(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: Test Site",
            'test-page/default.txt' => "Title: Test Page\n----\nText: This is test content.",
        ]);

        unset($_GET['pageIds']);

        $result = MetaKitController::getPagesWithContent();
        $this->assertEquals('success', $result['status']);

        $ids = array_map(fn($item) => $item['id'], $result['data']);
        $this->assertContains('site', $ids);
        $this->assertContains('test-page', $ids);
    }

    public function testExtractPageContentSkipsMetaFields(): void
    {
        $kirby = $this->makeKirby([
            'test-page/default.txt' => "Title: Test Page\n----\nText: This is a sufficiently long test content sentence for extraction.\n----\nMetatitle: Should Not Appear\n----\nOgdescription: Should Not Appear\n",
        ]);

        $page = $kirby->page('test-page');
        $reflection = new \ReflectionClass(MetaKitController::class);
        $method = $reflection->getMethod('extractPageContent');

        $content = $method->invoke(null, $page);

        $this->assertStringContainsString('Test Page', $content);
        $this->assertStringContainsString('sufficiently long test content', $content);
        $this->assertStringNotContainsString('Should Not Appear', $content);
    }

    public function testGenerateFieldRestoresLanguage(): void
    {
        $kirby = $this->makeKirby(
            [
                'site.txt' => "Title: Test Site",
                'test-page/default.txt' => "Title: Test Page\n----\nText: This is test content.",
                'test-page/default.de.txt' => "Title: Test Seite\n----\nText: Das ist Testinhalt.",
            ],
            [
                'tearoom1.meta-kit.api.key' => null,
                'tearoom1.meta-kit.api.model' => null,
            ],
            [
                [
                    'code' => 'en',
                    'name' => 'English',
                    'default' => true,
                ],
                [
                    'code' => 'de',
                    'name' => 'Deutsch',
                ],
            ]
        );

        $kirby->setCurrentLanguage('de');
        $this->assertEquals('de', $kirby->language()->code());

        $result = MetaKitController::generateField('test-page', 'metaTitle', 'en', false);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('de', $kirby->language()->code());
    }

    private function resetAiEnabledCache(): void
    {
        $reflection = new \ReflectionClass(MetaKit::class);
        $property = $reflection->getProperty('aiEnabledCache');
        $property->setValue(null, null);
    }
}
