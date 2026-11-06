<?php

namespace TearoomOne\Tests;

use Kirby\Data\Yaml;
use Kirby\Form\Field;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Blueprint labels (`label: meta-kit.…`) in the Panel, with the registered
 * plugin: every test runs in its own process.
 */
class PanelLabelsTest extends KirbyTestCase
{
    private const LANGUAGES = [
        ['code' => 'en', 'name' => 'English', 'default' => true],
        ['code' => 'sv', 'name' => 'Svenska'],
    ];

    private function makePanel(string $panelLanguage, array $options = [])
    {
        require_once dirname(__DIR__) . '/index.php';

        $kirby = $this->makeKirby(
            ['site.txt' => 'Title: Site', 'about/default.en.txt' => 'Title: About'],
            ['tearoom1.meta-kit' => $options],
            self::LANGUAGES
        );
        $kirby->setCurrentTranslation($panelLanguage);

        return $kirby;
    }

    private function label($kirby, string $name): string
    {
        $blueprint = Yaml::read(dirname(__DIR__) . '/blueprints/fields/meta-group.yml');

        $field = new Field('text', [
            'name' => $name,
            'model' => $kirby->page('about'),
            'label' => $blueprint['fields'][$name]['label'],
        ]);

        return $field->toArray()['label'];
    }

    /**
     * AI on by default: reading its settings while Kirby registered the
     * plugin loaded the Panel texts before the plugin's were there
     */
    #[RunInSeparateProcess]
    public function testLabelsAreTranslatedWithAiEnabledByDefault(): void
    {
        $kirby = $this->makePanel('en');

        $this->assertSame('Slug Validation', $this->label($kirby, 'slugInfo'));
        $this->assertSame('Meta Title', $this->label($kirby, 'metaTitle'));
    }

    #[RunInSeparateProcess]
    public function testLabelsAreTranslatedWithAiDisabled(): void
    {
        $kirby = $this->makePanel('de', ['ai.enabled' => false]);

        $this->assertSame('Slug-Prüfung', $this->label($kirby, 'slugInfo'));
    }

    /**
     * Kirby falls back to the key, not to English, for a Panel language
     * the plugin has no translation for
     */
    #[RunInSeparateProcess]
    public function testOtherPanelLanguagesFallBackToEnglish(): void
    {
        $kirby = $this->makePanel('sv_SE');

        $this->assertSame('Slug Validation', $this->label($kirby, 'slugInfo'));
        $this->assertSame('Author', $this->label($kirby, 'metaAuthor'));
    }

    #[RunInSeparateProcess]
    public function testAiRoutesAreRegisteredWhateverTheSettings(): void
    {
        $kirby = $this->makePanel('en', ['ai.enabled' => false]);
        $patterns = array_column($kirby->extensions('api')['routes'], 'pattern');

        $this->assertContains('meta-kit/generate', $patterns);
        $this->assertContains('meta-kit/review-page', $patterns);
    }
}
