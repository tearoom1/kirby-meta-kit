<?php

namespace TearoomOne\Tests;

use Kirby\Data\Txt;
use Kirby\Data\Yaml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use TearoomOne\ConfigHelper;
use TearoomOne\LlmsTxt;
use TearoomOne\Robots;

/**
 * Site settings that hold configuration are shared by all languages:
 * read from the default language, never from a copy left in a translation.
 *
 * Uses the registered plugin (real blueprints and hooks), so every test
 * runs in its own process.
 */
class SharedSiteSettingsTest extends KirbyTestCase
{
    private const LANGUAGES = [
        ['code' => 'de', 'name' => 'Deutsch', 'default' => true],
        ['code' => 'en', 'name' => 'English'],
        ['code' => 'it', 'name' => 'Italiano'],
    ];

    /**
     * Settings stored in the default language
     */
    private function defaultSettings(): array
    {
        return [
            'Title' => 'Seite',
            'Metatitle' => 'Meta DE',
            'Appendsitename' => 'true',
            'Appendsitenameto' => 'meta',
            'Titleseparator' => '–',
            'Robots' => 'noindex, follow',
            'Schemaname' => 'Schema DE',
            'Metakitsocialsites' => Yaml::encode([['platform' => 'instagram', 'url' => 'https://example.com/de']]),
            'Metakitsitemap' => $this->makeBlocksJson('mk-sitemap', ['priorityhome' => 0.5], 'sitemap-settings'),
            'Metakitopenrouter' => $this->makeBlocksJson('mk-openrouter', ['model' => 'de/model'], 'openrouter-settings'),
            'Metakitrobots' => $this->makeBlocksJson('mk-robots', [
                'enabled' => true,
                'customdirectives' => '# from DE',
                'llmstxt' => true,
            ], 'robots-settings'),
        ];
    }

    /**
     * Conflicting copies, as an older Meta Kit could leave them in a translation
     */
    private function conflictingCopies(): array
    {
        return [
            'Appendsitename' => 'false',
            'Appendsitenameto' => 'og',
            'Titleseparator' => '/',
            'Robots' => 'index, follow',
            'Schemaname' => 'Schema EN',
            'Metakitsocialsites' => Yaml::encode([['platform' => 'instagram', 'url' => 'https://example.com/en']]),
            'Metakitsitemap' => $this->makeBlocksJson('mk-sitemap', ['priorityhome' => 0.1], 'sitemap-settings'),
            'Metakitopenrouter' => $this->makeBlocksJson('mk-openrouter', ['model' => 'en/model'], 'openrouter-settings'),
            'Metakitrobots' => $this->makeBlocksJson('mk-robots', [
                'enabled' => true,
                'customdirectives' => '# from EN',
                'llmstxt' => false,
            ], 'robots-settings'),
        ];
    }

    private function makeSite(array $files, array $siteBlueprint = [], ?array $languages = self::LANGUAGES)
    {
        require_once dirname(__DIR__) . '/index.php';

        $content = array_map(fn ($fields) => is_array($fields) ? Txt::encode($fields) : $fields, $files);

        return $this->makeKirby($content, [], $languages, [
            'blueprints' => [
                'site' => array_replace_recursive(
                    ['tabs' => ['seo' => ['extends' => 'meta-kit/site']]],
                    $siteBlueprint
                ),
            ],
        ]);
    }

    /**
     * Raw fields of a content file, without Kirby's language fallback
     */
    private function rawFields($kirby, string $file): array
    {
        return array_change_key_case(Txt::decode(file_get_contents($kirby->root('content') . '/' . $file)));
    }

    public static function translationStates(): array
    {
        return [
            'translation without the settings' => ['missing'],
            'translation with equal copies' => ['equal'],
            'translation with conflicting copies' => ['conflicting'],
            'translation with empty copies' => ['empty'],
        ];
    }

    #[RunInSeparateProcess]
    #[DataProvider('translationStates')]
    public function testSharedSettingsComeFromTheDefaultLanguage(string $state): void
    {
        $copies = match ($state) {
            'missing' => [],
            'equal' => array_diff_key($this->defaultSettings(), ['Title' => 1, 'Metatitle' => 1]),
            'conflicting' => $this->conflictingCopies(),
            'empty' => array_map(fn () => '', $this->conflictingCopies()),
        };

        $kirby = $this->makeSite([
            'site.de.txt' => $this->defaultSettings(),
            'site.en.txt' => ['Title' => 'Site', 'Metatitle' => 'Meta EN', ...$copies],
        ]);
        $kirby->setCurrentLanguage('en');
        ConfigHelper::clearCache();

        // Title settings are shared…
        $settings = ConfigHelper::getSiteSettings();
        $this->assertTrue($settings['appendSiteName']);
        $this->assertSame('meta', $settings['appendSiteNameTo']);
        $this->assertSame('–', $settings['titleSeparator']);
        // …the site name stays translated
        $this->assertSame('Meta EN', $settings['siteMetaTitle']);

        // Settings blocks
        $this->assertEquals(0.5, ConfigHelper::getSitemapSettings()['sitemap.priorityHome']);
        $this->assertSame('de/model', ConfigHelper::getAiSettings()['api.model']);
        $this->assertTrue(LlmsTxt::isEnabled());
        $robotsTxt = (new Robots($kirby))->generate();
        $this->assertStringContainsString('# from DE', $robotsTxt);
        $this->assertStringNotContainsString('# from EN', $robotsTxt);

        // Head output
        $this->assertSame('noindex, follow', ConfigHelper::siteSetting('robots')->value());
        $this->assertSame('Schema DE', ConfigHelper::siteSetting('schemaName')->value());
        $this->assertSame(
            'https://example.com/de',
            ConfigHelper::siteSetting('metaKitSocialSites')->toStructure()->first()->url()->value()
        );
    }

    #[RunInSeparateProcess]
    public function testBlueprintSharesTheSettingsAndKeepsTheWordsTranslatable(): void
    {
        $kirby = $this->makeSite(['site.de.txt' => $this->defaultSettings()]);
        $blueprint = $kirby->site()->blueprint();

        foreach (ConfigHelper::SHARED_SITE_FIELDS as $name) {
            $this->assertFalse($blueprint->field($name)['translate'] ?? true, $name . ' must be shared');
            $this->assertTrue(ConfigHelper::isSharedSiteField($kirby->site(), $name), $name);
        }

        foreach (['metaTitle', 'metaDescription', 'metaAuthor', 'ogImage'] as $name) {
            $this->assertTrue($blueprint->field($name)['translate'] ?? true, $name . ' must stay translatable');
            $this->assertFalse(ConfigHelper::isSharedSiteField($kirby->site(), $name), $name);
        }

        // A stale copy of appendSiteName must not hide the translated site name
        $this->assertArrayNotHasKey('when', $blueprint->field('metaTitle'));
    }

    #[RunInSeparateProcess]
    public function testSavingTheSiteRemovesCopiesFromAllTranslations(): void
    {
        $kirby = $this->makeSite([
            'site.de.txt' => $this->defaultSettings(),
            'site.en.txt' => ['Title' => 'Site', 'Metatitle' => 'Meta EN', ...$this->conflictingCopies()],
            // Unsaved Italian changes of another editor
            '_changes/site.it.txt' => ['Title' => 'Sito', 'Metatitle' => 'Meta IT', 'Lock' => 'other-editor', ...$this->conflictingCopies()],
            'site.it.txt' => ['Title' => 'Sito'],
        ]);

        $kirby->impersonate('kirby', fn () => $kirby->site()->update(['metaDescription' => 'Beschreibung'], 'de'));

        $shared = array_map('strtolower', ConfigHelper::SHARED_SITE_FIELDS);

        $english = $this->rawFields($kirby, 'site.en.txt');
        $this->assertSame([], array_intersect(array_keys($english), $shared));
        $this->assertSame('Meta EN', $english['metatitle']);

        $italianChanges = $this->rawFields($kirby, '_changes/site.it.txt');
        $this->assertSame([], array_intersect(array_keys($italianChanges), $shared));
        $this->assertSame('Meta IT', $italianChanges['metatitle']);
        // The other editor keeps the lock of their changes
        $this->assertSame('other-editor', $italianChanges['lock']);

        // The default language keeps every setting
        $german = $this->rawFields($kirby, 'site.de.txt');
        $this->assertSame('–', $german['titleseparator']);
        $this->assertSame('Beschreibung', $german['metadescription']);

        // The Panel of the translation now shows the shared values
        $kirby->setCurrentLanguage('en');
        $this->assertSame('–', $kirby->site()->content('en')->get('titleSeparator')->value());
    }

    #[RunInSeparateProcess]
    public function testPublishingTranslatedChangesKeepsTheSharedSettings(): void
    {
        $kirby = $this->makeSite([
            'site.de.txt' => $this->defaultSettings(),
            'site.en.txt' => ['Title' => 'Site'],
            'site.it.txt' => ['Title' => 'Sito', ...$this->conflictingCopies()],
        ]);
        $kirby->setCurrentLanguage('en');

        // Panel flow in English: autosave to the changes version, then publish
        $kirby->impersonate('kirby', function () use ($kirby) {
            $kirby->site()->version('changes')->save(
                ['metaTitle' => 'Meta EN', 'titleSeparator' => '/', 'appendSiteName' => 'false'],
                'en'
            );
            $kirby->site()->version('changes')->publish('en');
        });

        $english = $this->rawFields($kirby, 'site.en.txt');
        $this->assertSame('Meta EN', $english['metatitle']);
        $this->assertArrayNotHasKey('titleseparator', $english);
        $this->assertArrayNotHasKey('appendsitename', $english);

        // Publishing fires the site update hook, which cleans Italian too
        $this->assertArrayNotHasKey('titleseparator', $this->rawFields($kirby, 'site.it.txt'));

        ConfigHelper::clearCache();
        $this->assertSame('–', ConfigHelper::getSiteSettings()['titleSeparator']);
        $this->assertTrue(ConfigHelper::getSiteSettings()['appendSiteName']);
    }

    #[RunInSeparateProcess]
    public function testInitializationWritesTheDefaultLanguageWhileAnotherIsCurrent(): void
    {
        $kirby = $this->makeSite([
            'site.de.txt' => ['Title' => 'Seite'],
            'site.en.txt' => ['Title' => 'Site'],
        ]);

        // Loading the plugin already initialized the blocks: start over
        // with an empty default language and English as current language
        file_put_contents($kirby->root('content') . '/site.de.txt', Txt::encode(['Title' => 'Seite']));
        \Kirby\Content\VersionCache::reset();
        $kirby->setCurrentLanguage('en');

        $hooks = require dirname(__DIR__) . '/src/hooks.php';
        $hooks['system.loadPlugins:after']();

        $german = $this->rawFields($kirby, 'site.de.txt');
        $robots = json_decode($german['metakitrobots'], true)[0]['content'];
        $openrouter = json_decode($german['metakitopenrouter'], true)[0]['content'];
        $sitemap = json_decode($german['metakitsitemap'], true)[0]['content'];

        // Nested fields of the blocks survive, also the shared ones
        // (toggles are stored as "true")
        $this->assertTrue(filter_var($robots['enabled'] ?? null, FILTER_VALIDATE_BOOLEAN));
        $this->assertTrue(filter_var($robots['includesitemap'] ?? null, FILTER_VALIDATE_BOOLEAN));
        $this->assertSame('google/gemma-4-31b-it:free', $openrouter['model']);
        $this->assertEquals(1.0, $sitemap['priorityhome']);

        // Nothing landed in the translation, and the language is restored
        $this->assertArrayNotHasKey('metakitrobots', $this->rawFields($kirby, 'site.en.txt'));
        $this->assertSame('en', $kirby->language()->code());
    }

    #[RunInSeparateProcess]
    public function testSiteBlueprintCanKeepAFieldTranslatable(): void
    {
        $kirby = $this->makeSite(
            [
                'site.de.txt' => $this->defaultSettings(),
                'site.en.txt' => ['Title' => 'Site', ...$this->conflictingCopies()],
            ],
            ['tabs' => ['seo' => ['columns' => ['social' => ['fields' => ['metaKitSocialSites' => ['translate' => true]]]]]]]
        );
        $kirby->setCurrentLanguage('en');

        $this->assertFalse(ConfigHelper::isSharedSiteField($kirby->site(), 'metaKitSocialSites'));
        $this->assertSame(
            'https://example.com/en',
            ConfigHelper::siteSetting('metaKitSocialSites')->toStructure()->first()->url()->value()
        );

        // The cleanup leaves the translated profiles alone
        $kirby->impersonate('kirby', fn () => $kirby->site()->update(['metaDescription' => 'Beschreibung'], 'de'));
        $english = $this->rawFields($kirby, 'site.en.txt');
        $this->assertArrayHasKey('metakitsocialsites', $english);
        $this->assertArrayNotHasKey('titleseparator', $english);
    }

    #[RunInSeparateProcess]
    public function testSingleLanguageSitesReadAndSaveAsBefore(): void
    {
        $kirby = $this->makeSite(['site.txt' => $this->defaultSettings()], languages: null);

        $settings = ConfigHelper::getSiteSettings();
        $this->assertSame('–', $settings['titleSeparator']);
        $this->assertSame('Meta DE', $settings['siteMetaTitle']);
        $this->assertSame('de/model', ConfigHelper::getAiSettings()['api.model']);

        $kirby->impersonate('kirby', fn () => $kirby->site()->update(['metaDescription' => 'Beschreibung']));
        $this->assertSame('–', $this->rawFields($kirby, 'site.txt')['titleseparator']);
    }
}
