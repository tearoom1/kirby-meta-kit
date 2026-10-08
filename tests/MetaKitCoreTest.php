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

        file_put_contents(
            $kirby->root('content') . '/site.txt',
            "Title: Test Site\n----\nMetatitle: Updated Title\n"
        );
        $settingsCached = ConfigHelper::getSiteSettings();
        $this->assertEquals('Initial Title', $settingsCached['siteMetaTitle']);

        ConfigHelper::clearCache();
        $reflection = new \ReflectionClass(ConfigHelper::class);
        $cache = $reflection->getProperty('siteSettingsCache');
        $this->assertNull($cache->getValue());
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

    public function testCustomSiteModelIsUsedWhenOtherModelIsSelected(): void
    {
        $this->resetAiEnabledCache();
        $this->makeKirby(
            [
                'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    apiKey: site-key\n    model: custom\n    customModel: ' openai/gpt-6-luna '\n",
            ],
            [
                'tearoom1.meta-kit.api.key' => null,
                'tearoom1.meta-kit.api.model' => null,
            ]
        );
        ConfigHelper::clearCache();

        $this->assertSame('openai/gpt-6-luna', ConfigHelper::getOpenRouterSettings()['api.model']);
        $this->assertTrue(MetaKit::isAiEnabled());
    }

    public function testMistralProviderUsesPresetEndpointAndDefaultModel(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    provider: mistral\n    apiKey: site-key\n    model: openai/gpt-6-luna\n",
        ]);

        $settings = ConfigHelper::getOpenRouterSettings();

        $this->assertSame('mistral', $settings['api.provider']);
        $this->assertSame('https://api.mistral.ai/v1/chat/completions', $settings['api.endpoint']);
        // The OpenRouter model field is ignored for Mistral
        $this->assertSame('mistral-small-latest', $settings['api.model']);
    }

    public function testMistralModelFromPanel(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    provider: mistral\n    apiKey: site-key\n    mistralModel: mistral-large-latest\n",
        ]);

        $this->assertSame('mistral-large-latest', ConfigHelper::getOpenRouterSettings()['api.model']);
    }

    public function testCustomProviderUsesPanelEndpointAndModel(): void
    {
        $this->resetAiEnabledCache();
        $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    provider: custom\n    apiKey: site-key\n    endpoint: https://llm.example.com/v1/chat/completions\n    endpointModel: llama3.3\n",
        ]);

        $settings = ConfigHelper::getOpenRouterSettings();

        $this->assertSame('https://llm.example.com/v1/chat/completions', $settings['api.endpoint']);
        $this->assertSame('llama3.3', $settings['api.model']);
        $this->assertTrue(MetaKit::isAiEnabled());
    }

    public function testCustomProviderWithoutEndpointDisablesAi(): void
    {
        $this->resetAiEnabledCache();
        $this->makeKirby([
            'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    provider: custom\n    apiKey: site-key\n    endpointModel: llama3.3\n",
        ]);

        $this->assertFalse(MetaKit::isAiEnabled());
    }

    public function testConfigOverridesPanelButNullDoesNot(): void
    {
        $this->makeKirby(
            [
                'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    apiKey: site-key\n    model: anthropic/claude-sonnet-5.5\n    reasoning: low\n",
            ],
            [
                'tearoom1.meta-kit' => [
                    'api.model' => 'openai/gpt-6-luna',
                    'api.key' => null,
                    'api.reasoning' => null,
                ],
            ]
        );

        $settings = ConfigHelper::getOpenRouterSettings();

        $this->assertSame('openai/gpt-6-luna', $settings['api.model']);
        $this->assertSame('site-key', $settings['api.key']);
        $this->assertSame('low', $settings['api.reasoning']);
    }

    public function testEmptyCustomSiteModelDisablesAi(): void
    {
        $this->resetAiEnabledCache();
        $this->makeKirby(
            [
                'site.txt' => "Title: Test Site\n----\nMetaKitOpenrouter:\n- type: mk-openrouter\n  content:\n    apiKey: site-key\n    model: custom\n",
            ],
            [
                'tearoom1.meta-kit.api.key' => null,
                'tearoom1.meta-kit.api.model' => null,
            ]
        );

        $this->assertFalse(MetaKit::isAiEnabled());
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

    public function testGenerationUsesConfiguredLocaleWhenLanguageContextIsNull(): void
    {
        $kirby = $this->makeKirby(
            ['site.txt' => "Title: Test Site"],
            ['locale' => 'de_DE.UTF-8']
        );

        $metaKit = new class($kirby) extends MetaKit
        {
            public array $prompts = [];

            protected function callApi(string $prompt, int $maxTokens): string
            {
                $this->prompts[] = $prompt;

                return $maxTokens === 50
                    ? 'Deutscher Meta-Titel'
                    : str_repeat('Deutsche Beschreibung ', 7);
            }
        };

        $metaKit->generateTitle(
            'Deutscher Seiteninhalt',
            ['language' => null, 'fieldType' => 'title']
        );
        $metaKit->generateDescription(
            'Deutscher Seiteninhalt',
            ['language' => null, 'fieldType' => 'description']
        );

        $this->assertCount(2, $metaKit->prompts);
        $this->assertStringContainsString('in De-DE', $metaKit->prompts[0]);
        $this->assertStringContainsString('in De-DE', $metaKit->prompts[1]);
    }

    public function testSanitizeDescriptionTrimsWarningLengthBackToOptimalMax(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Test Site",
        ]);

        $metaKit = new MetaKit($kirby);
        $reflection = new \ReflectionClass(MetaKit::class);
        $method = $reflection->getMethod('sanitizeDescription');

        $description = 'This meta description is intentionally a bit too long for the optimal range, but it should still be trimmed back cleanly at a word boundary without sounding broken or abrupt.';
        $sanitized = $method->invoke($metaKit, $description, 'description', null);

        $this->assertLessThanOrEqual(160, mb_strlen($sanitized));
        $this->assertNotSame($description, $sanitized);
        $this->assertDoesNotMatchRegularExpression('/[,:;\-]$/', $sanitized);
    }

    public function testGenerateDescriptionRetriesWhenFirstDraftIsTooLong(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Test Site",
        ]);

        $metaKit = new class($kirby) extends MetaKit
        {
            public array $prompts = [];
            private array $responses = [
                'This meta description is too long for the optimal range because it keeps going with extra detail that should trigger a rewrite attempt before the plugin accepts it.',
                'Clear summary of the page content with a tighter, properly sized description that stays concise, specific, useful, and comfortably within the ideal length range.'
            ];

            protected function callApi(string $prompt, int $maxTokens): string
            {
                $this->prompts[] = $prompt;
                return array_shift($this->responses) ?? '';
            }
        };

        $description = $metaKit->generateDescription(
            'This page explains the most important content in a straightforward way.',
            ['language' => 'en', 'fieldType' => 'description']
        );

        $this->assertGreaterThanOrEqual(2, count($metaKit->prompts));
        $this->assertStringContainsString('Rewrite this meta description', $metaKit->prompts[1]);
        $this->assertLessThanOrEqual(160, mb_strlen($description));
        $this->assertGreaterThanOrEqual(140, mb_strlen($description));
        $this->assertStringStartsWith(
            'Clear summary of the page content with a tighter, properly sized description',
            $description
        );
    }

    public function testOpenRouterErrorsIncludeProviderMetadata(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Test Site",
        ]);

        $metaKit = new MetaKit($kirby);
        $reflection = new \ReflectionClass(MetaKit::class);
        $method = $reflection->getMethod('formatOpenRouterError');

        $message = $method->invoke($metaKit, [
            'error' => [
                'message' => 'Provider returned error',
                'code' => 502,
                'metadata' => [
                    'provider_name' => 'OpenAI',
                    'raw' => '{"error":{"message":"The upstream model is temporarily unavailable"}}',
                ],
            ],
        ], '', 'google/gemma-4-31b-it:free');

        $this->assertStringContainsString('Provider returned error', $message);
        $this->assertStringContainsString('model: google/gemma-4-31b-it:free', $message);
        $this->assertStringContainsString('provider: OpenAI', $message);
        $this->assertStringContainsString('code: 502', $message);
        $this->assertStringContainsString('The upstream model is temporarily unavailable', $message);
    }

    private function resetAiEnabledCache(): void
    {
        $reflection = new \ReflectionClass(MetaKit::class);
        $property = $reflection->getProperty('aiEnabledCache');
        $property->setValue(null, null);
    }
}
