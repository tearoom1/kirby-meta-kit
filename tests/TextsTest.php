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

        $field = new \Kirby\Form\Field('select', [
            'name' => 'robots',
            'model' => $kirby->page('about'),
            ...array_intersect_key($blueprint['fields']['robots'], ['label' => 1, 'options' => 1]),
        ]);
        $options = $field->toArray()['options'];

        $this->assertSame('Robots-Anweisung festlegen', $field->toArray()['label']);
        $this->assertSame('Indexieren, Links folgen', $options[0]['text']);
    }

    public function testCustomFieldAndSectionLabelsAreTranslated(): void
    {
        $kirby = $this->germanPanel();

        \Kirby\Form\Field::$types['mk-review'] = require dirname(__DIR__) . '/fields/mk-review/index.php';
        $review = new \Kirby\Form\Field('mk-review', ['name' => 'review', 'model' => $kirby->page('about')]);
        $this->assertSame('KI-Inhaltsprüfung', $review->toArray()['label']);

        \Kirby\Cms\Section::$types['seo-preview'] = require dirname(__DIR__) . '/sections/preview.php';
        $section = new \Kirby\Cms\Section('seo-preview', ['name' => 'preview', 'model' => $kirby->page('about')]);
        $this->assertSame('SEO-Vorschau', $section->label());
    }
}
