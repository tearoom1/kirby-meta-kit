<?php

namespace TearoomOne\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use TearoomOne\ConfigHelper;
use TearoomOne\Robots;

/**
 * Every option works flat ('ai.enabled'), nested ('ai' => ['enabled']),
 * mixed and with the full plugin prefix in config.php.
 * The registered plugin (with its defaults from index.php) is needed, so
 * every test runs in its own process to keep the other tests plugin-free.
 */
#[RunTestsInSeparateProcesses]
class OptionNotationTest extends KirbyTestCase
{
    protected function makeKirby(
        array $contentFiles,
        array $options = [],
        ?array $languages = null,
        array $extraConfig = []
    ): \Kirby\Cms\App {
        require_once dirname(__DIR__) . '/index.php';

        return parent::makeKirby($contentFiles, $options, $languages, $extraConfig);
    }

    public static function aiEnabledNotations(): array
    {
        return [
            'flat' => [['tearoom1.meta-kit' => ['ai.enabled' => false]]],
            'nested' => [['tearoom1.meta-kit' => ['ai' => ['enabled' => false]]]],
            'prefixed' => [['tearoom1.meta-kit.ai.enabled' => false]],
        ];
    }

    #[DataProvider('aiEnabledNotations')]
    public function testAiEnabledInEveryNotation(array $options): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], $options);

        $this->assertFalse(option('tearoom1.meta-kit.ai.enabled'));

        // Sibling defaults of the same group survive a nested override
        $this->assertSame('formal', option('tearoom1.meta-kit.ai.tone'));
    }

    public function testGroupedDefaultsApplyWithoutConfig(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site']);

        $this->assertTrue(option('tearoom1.meta-kit.ai.enabled'));
        $this->assertFalse(option('tearoom1.meta-kit.review.enabled'));
        $this->assertSame('formal', option('tearoom1.meta-kit.ai.tone'));
        $this->assertSame(['error'], ConfigHelper::getSitemapSettings()['sitemap.exclude']);
        $this->assertSame('formal', ConfigHelper::getAiSettings()['ai.tone']);
    }

    public static function aiToneNotations(): array
    {
        return [
            'flat' => [['tearoom1.meta-kit' => ['ai.tone' => 'casual', 'api.key' => 'k']]],
            'nested' => [['tearoom1.meta-kit' => ['ai' => ['tone' => 'casual'], 'api' => ['key' => 'k']]]],
            'prefixed' => [['tearoom1.meta-kit.ai.tone' => 'casual', 'tearoom1.meta-kit.api.key' => 'k']],
        ];
    }

    #[DataProvider('aiToneNotations')]
    public function testAiSettingsInEveryNotation(array $options): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], $options);

        $settings = ConfigHelper::getAiSettings();

        $this->assertSame('casual', $settings['ai.tone']);
        $this->assertSame('k', $settings['api.key']);
    }

    public function testNestedEmptyModelStillDisablesAi(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], [
            'tearoom1.meta-kit' => ['api' => ['model' => '']],
        ]);

        $this->assertSame('', ConfigHelper::getAiSettings()['api.model']);
    }

    public static function sitemapNotations(): array
    {
        return [
            'flat' => [['tearoom1.meta-kit' => [
                'sitemap.exclude' => ['secret'],
                'sitemap.changefreq.default' => 'weekly',
                'sitemap.changefreq.templates' => ['home' => 'hourly'],
            ]]],
            'nested' => [['tearoom1.meta-kit' => ['sitemap' => [
                'exclude' => ['secret'],
                'changefreq' => ['default' => 'weekly', 'templates' => ['home' => 'hourly']],
            ]]]],
            'mixed' => [['tearoom1.meta-kit' => ['sitemap' => [
                'exclude' => ['secret'],
                'changefreq.default' => 'weekly',
                'changefreq.templates' => ['home' => 'hourly'],
            ]]]],
            'prefixed' => [[
                'tearoom1.meta-kit.sitemap.exclude' => ['secret'],
                'tearoom1.meta-kit.sitemap.changefreq.default' => 'weekly',
                'tearoom1.meta-kit.sitemap.changefreq.templates' => ['home' => 'hourly'],
            ]],
        ];
    }

    #[DataProvider('sitemapNotations')]
    public function testSitemapSettingsInEveryNotation(array $options): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], $options);

        $settings = ConfigHelper::getSitemapSettings();

        // Lists replace the default instead of merging into it
        $this->assertSame(['secret'], $settings['sitemap.exclude']);
        $this->assertSame('weekly', $settings['sitemap.changefreq.default']);

        // Array values stay whole values, not flattened further
        $this->assertSame(['home' => 'hourly'], $settings['sitemap.changefreq.templates']);
    }

    public static function robotsNotations(): array
    {
        return [
            'flat' => [['tearoom1.meta-kit' => ['robots.blockBadBots' => true, 'robots.includeSitemap' => false]]],
            'nested' => [['tearoom1.meta-kit' => ['robots' => ['blockBadBots' => true, 'includeSitemap' => false]]]],
            'prefixed group' => [['tearoom1.meta-kit.robots' => ['blockBadBots' => true, 'includeSitemap' => false]]],
            'prefixed flat' => [['tearoom1.meta-kit.robots.blockBadBots' => true, 'tearoom1.meta-kit.robots.includeSitemap' => false]],
        ];
    }

    #[DataProvider('robotsNotations')]
    public function testRobotsGroupInEveryNotation(array $options): void
    {
        // Panel robots settings exist but leave these toggles unset
        $robotsBlocks = $this->makeBlocksJson('mk-robots', ['enabled' => 'true']);
        $this->makeKirby(['site.txt' => "Title: Site\n----\nMetakitrobots: {$robotsBlocks}\n"], $options);

        $this->assertSame(['blockBadBots' => true, 'includeSitemap' => false], ConfigHelper::optionGroup('robots'));

        $content = (new Robots(kirby()))->generate();

        $this->assertStringContainsString('AhrefsBot', $content);
        $this->assertStringNotContainsString('Sitemap:', $content);
    }

    public function testFlatGroupKeyWinsOverNested(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], [
            'tearoom1.meta-kit' => [
                'robots' => ['blockBadBots' => false, 'blockAiCrawlers' => true],
                'robots.blockBadBots' => true,
            ],
        ]);

        $this->assertSame(
            ['blockBadBots' => true, 'blockAiCrawlers' => true],
            ConfigHelper::optionGroup('robots')
        );
    }

    public function testValidationAcceptsFlatKeys(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], [
            'tearoom1.meta-kit' => ['validation.ranges.title.optimal.max' => 50],
        ]);

        $this->assertSame(['min' => 20, 'max' => 50], ConfigHelper::getValidationRanges('title')['optimal']);
    }
}
