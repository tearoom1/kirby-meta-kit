<?php

namespace TearoomOne\Tests;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use TearoomOne\MetaKitController;
use TearoomOne\Texts;

class TextsTest extends KirbyTestCase
{
    public function testFallsBackToEnglishAndFillsPlaceholders(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site']);

        $this->assertSame('OpenRouter API key is not configured', Texts::get('error.apiKeyMissing', ['provider' => 'OpenRouter']));
        $this->assertSame('meta-kit.unknown.key', Texts::get('unknown.key'));
    }

    public function testUsesThePanelLanguage(): void
    {
        $kirby = $this->makeKirby(['site.txt' => 'Title: Site'], extraConfig: [
            'translations' => ['de' => json_decode(file_get_contents(dirname(__DIR__) . '/translations/de.json'), true)],
        ]);
        $kirby->setCurrentTranslation('de');

        $this->assertSame('Für Mistral ist kein API-Key eingetragen', Texts::get('error.apiKeyMissing', ['provider' => 'Mistral']));
        $this->assertSame('Seite nicht gefunden', MetaKitController::generateField('missing', 'metaTitle')['message']);
    }

    private function germanPanel()
    {
        $kirby = $this->makeKirby(['site.txt' => 'Title: Site', 'about/default.txt' => 'Title: About'], extraConfig: [
            'translations' => [
                'en' => json_decode(file_get_contents(dirname(__DIR__) . '/translations/en.json'), true),
                'de' => json_decode(file_get_contents(dirname(__DIR__) . '/translations/de.json'), true),
            ],
        ]);
        $kirby->setCurrentTranslation('de');
        \Kirby\Toolkit\I18n::$locale = 'de';

        return $kirby;
    }

    public function testBlueprintOptionTextsAreTranslated(): void
    {
        $kirby = $this->germanPanel();
        $blueprint = \Kirby\Data\Yaml::read(dirname(__DIR__) . '/blueprints/fields/meta-group.yml');

        $field = new \Kirby\Form\Field\SelectField(...[
            'model' => $kirby->page('about'),
            'name' => 'robots',
            ...array_intersect_key($blueprint['fields']['robots'], ['label' => 1, 'options' => 1]),
        ]);
        $options = $field->toArray()['options'];

        $this->assertSame('Robots-Anweisung festlegen', $field->toArray()['label']);
        $this->assertSame('Indexieren, Links folgen', $options[0]['text']);
    }

    public function testCustomFieldLabelsAreTranslated(): void
    {
        $kirby = $this->germanPanel();

        $review = new \TearoomOne\Fields\ReviewField(model: $kirby->page('about'), name: 'review');
        $this->assertSame('KI-Inhaltsprüfung', $review->toArray()['label']);

        $preview = new \TearoomOne\Fields\PreviewField(model: $kirby->page('about'), name: 'preview');
        $this->assertSame('SEO-Vorschau', $preview->toArray()['label']);
    }
}
