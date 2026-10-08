<?php

namespace TearoomOne\Tests;

use TearoomOne\ConfigHelper;
use TearoomOne\MetaHelper;

class ValidationSettingsTest extends KirbyTestCase
{
    public function testDefaultsComeFromSharedJson(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site']);

        $json = json_decode(file_get_contents(dirname(__DIR__) . '/config/validation-defaults.json'), true);
        $settings = ConfigHelper::getValidationSettings();

        $this->assertSame($json['ranges'], $settings['ranges']);
        $this->assertSame($json['slug'], $settings['slug']);
        $this->assertSame([], $settings['templates']);
    }

    public function testPartialGlobalOverrideKeepsRemainingValues(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], [
            'tearoom1.meta-kit' => ['validation' => ['ranges' => ['title' => ['optimal' => ['max' => 50]]]]],
        ]);

        $title = ConfigHelper::getValidationRanges('title');

        $this->assertSame(['min' => 20, 'max' => 50], $title['optimal']);
        $this->assertSame(['min' => 15, 'max' => 75], $title['warning']);
    }

    public function testTemplateRangesAcceptFlatAndNestedFormat(): void
    {
        $this->makeKirby(['site.txt' => 'Title: Site'], [
            'tearoom1.meta-kit' => ['validation' => ['templates' => [
                // Format from the README
                'product' => ['title' => ['optimal' => ['min' => 25, 'max' => 45]]],
                // Format with a `ranges` key, as used next to `slug`
                'article' => [
                    'ranges' => ['title' => ['optimal' => ['min' => 40, 'max' => 60]]],
                    'slug' => ['words' => ['optimal' => ['min' => 5]]],
                ],
            ]]],
        ]);

        $this->assertSame(['min' => 25, 'max' => 45], ConfigHelper::getValidationRanges('title', 'product')['optimal']);
        $this->assertSame(['min' => 40, 'max' => 60], ConfigHelper::getValidationRanges('metaTitle', 'article')['optimal']);
        // Untouched values fall back to the global rules
        $this->assertSame(['min' => 15, 'max' => 75], ConfigHelper::getValidationRanges('title', 'article')['warning']);
        $this->assertSame(['min' => 140, 'max' => 160], ConfigHelper::getValidationRanges('description', 'article')['optimal']);
        // Unknown templates use the global rules
        $this->assertSame(['min' => 20, 'max' => 60], ConfigHelper::getValidationRanges('title', 'blog')['optimal']);

        $slug = ConfigHelper::getSlugValidation('article');
        $this->assertSame(['min' => 5, 'max' => 8], $slug['words']['optimal']);
        $this->assertSame(['min' => 0, 'max' => 2], $slug['depth']['optimal']);
    }

    public function testOutputKeepsTextsWithinTheWarningRange(): void
    {
        $og = str_repeat('word ', 50); // 250 characters, within the og range (max 300)
        $meta = str_repeat('a', 170);  // within the description warning range (max 176)
        $long = str_repeat('word ', 80); // 400 characters

        $kirby = $this->makeKirby([
            'site.txt' => 'Title: Site',
            'a/default.txt' => "Title: A\n----\nMetadescription: {$meta}\n----\nOgdescription: " . trim($og) . "\n",
            'b/default.txt' => "Title: B\n----\nOgdescription: " . trim($long) . "\n",
        ]);
        $site = $kirby->site();

        $this->assertSame($meta, MetaHelper::buildDescription($kirby->page('a'), $site));
        $this->assertSame(trim($og), MetaHelper::buildOgDescription($kirby->page('a'), $site));
        $this->assertLessThanOrEqual(300, mb_strlen(MetaHelper::buildOgDescription($kirby->page('b'), $site)));
    }

    public function testOutputLimitFollowsTemplateRanges(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => 'Title: Site',
            'a/article.txt' => "Title: A\n----\nMetadescription: " . str_repeat('a', 190) . "\n",
        ], [
            'tearoom1.meta-kit' => ['validation' => ['templates' => [
                'article' => ['description' => ['warning' => ['max' => 200]]],
            ]]],
        ]);

        $this->assertSame(200, MetaHelper::outputLimit('description', $kirby->page('a')));
        $this->assertSame(190, mb_strlen(MetaHelper::buildDescription($kirby->page('a'), $kirby->site())));
    }
}
