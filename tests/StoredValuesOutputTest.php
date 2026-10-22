<?php

namespace TearoomOne\Tests;

use Kirby\Data\Yaml;
use TearoomOne\ConfigHelper;
use TearoomOne\LlmsTxt;
use TearoomOne\MetaHelper;

/**
 * Stored values are rendered as they are: no truncation, no crash without a
 * site title, no empty meta tags.
 */
class StoredValuesOutputTest extends KirbyTestCase
{
    protected function setUp(): void
    {
        ConfigHelper::clearCache();
    }

    public function testStoredDescriptionLongerThanTheRangeIsRenderedInFull(): void
    {
        // 189 characters, above the description warning max (176)
        $description = rtrim(str_repeat('Sonnenaufgang ', 14)) . ' am Meer.';
        $description = mb_substr($description . str_repeat('x', 189), 0, 189);
        $og = trim(str_repeat('Open graph text ', 25)); // ~400 characters, above og max (300)

        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site\n",
            'a/default.txt' => "Title: A\n----\nMetadescription: {$description}\n",
            'b/default.txt' => "Title: B\n----\nOgdescription: {$og}\n",
        ]);
        ConfigHelper::clearCache();

        $html = $this->render($kirby->page('a'));
        $this->assertSame(189, mb_strlen(MetaHelper::buildDescription($kirby->page('a'), $kirby->site())));
        $this->assertStringContainsString('<meta name="description" content="' . $description . '">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="' . $description . '">', $html);
        $this->assertStringContainsString('<meta name="twitter:description" content="' . $description . '">', $html);
        $this->assertStringContainsString('"description": "' . $description . '"', $html);
        $this->assertStringNotContainsString(' …', $html);

        $this->assertSame($og, MetaHelper::buildOgDescription($kirby->page('b'), $kirby->site()));
        $this->assertStringContainsString('<meta property="og:description" content="' . $og . '">', $this->render($kirby->page('b')));
    }

    public function testExplicitLimitStillShortensDerivedText(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site\n",
            'a/default.txt' => "Title: A\n----\nMetadescription: " . trim(str_repeat('word ', 60)) . "\n",
        ]);

        $short = MetaHelper::buildDescription($kirby->page('a'), $kirby->site(), 50);
        $this->assertLessThanOrEqual(50, mb_strlen($short));
        $this->assertStringEndsWith(' …', $short);
    }

    public function testDescriptionFieldsHaveNoHardMaxlength(): void
    {
        foreach (['meta-group', 'seo-group', 'site-seo-group'] as $name) {
            $blueprint = Yaml::read(dirname(__DIR__) . '/blueprints/fields/' . $name . '.yml');
            foreach ($blueprint['fields'] as $key => $field) {
                if (($field['type'] ?? null) === 'mk-description') {
                    $this->assertArrayNotHasKey('maxlength', $field, "{$name}.{$key} must not block saving long values");
                }
            }
        }
    }

    public function testSiteWithoutTitleRendersWithoutSiteName(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Metadescription: Site description\n",
            'parent/default.txt' => "Title: Parent\n",
            'parent/child/default.txt' => "Title: Child\n",
            'home/default.txt' => "Title: Home\n",
        ]);
        ConfigHelper::clearCache();

        $html = $this->render($kirby->page('parent/child'));

        $this->assertStringContainsString('<title>Child</title>', $html);
        $this->assertStringNotContainsString('og:site_name', $html);
        $this->assertStringNotContainsString('"name": null', $html);
        $this->assertStringNotContainsString('"name": ""', $html);
        // Breadcrumb home entry falls back to the home page title
        $this->assertStringContainsString('"name": "Home"', $html);

        $this->assertStringStartsWith('# ', (new LlmsTxt($kirby))->toMarkdown());
    }

    public function testSiteTitleIsUsedForSiteName(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Inner & Sunrise\n",
            'a/default.txt' => "Title: A\n",
        ]);
        ConfigHelper::clearCache();

        $html = $this->render($kirby->page('a'));
        $this->assertStringContainsString('<meta property="og:site_name" content="Inner &amp; Sunrise">', $html);
        $this->assertStringContainsString('<title>A | Inner &amp; Sunrise</title>', $html);
    }

    public function testEmptyDescriptionOmitsTheTags(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site\n",
            'a/default.txt' => "Title: A\n----\nMetadescription: \n----\nRobots: \n",
        ]);
        ConfigHelper::clearCache();

        $html = $this->render($kirby->page('a'));

        $this->assertStringNotContainsString('name="description"', $html);
        $this->assertStringNotContainsString('og:description', $html);
        $this->assertStringNotContainsString('twitter:description', $html);
        $this->assertStringNotContainsString('name="robots"', $html);
        $this->assertStringNotContainsString('content=""', $html);
        $this->assertStringNotContainsString('"description"', $html);
    }

    private function render($page): string
    {
        $site = site();
        ob_start();
        include __DIR__ . '/../snippets/seo.php';
        return ob_get_clean();
    }
}
