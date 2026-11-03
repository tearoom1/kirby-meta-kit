<?php

namespace TearoomOne\Tests;

use TearoomOne\ConfigHelper;
use TearoomOne\LlmsTxt;
use TearoomOne\MetaHelper;

/**
 * Every text in the head is escaped exactly once: "&" becomes "&amp;" in
 * HTML and "\u0026" in JSON-LD, never "&amp;amp;" or a literal "&amp;".
 */
class OutputEscapingTest extends KirbyTestCase
{
    private const NBSP = "\u{00A0}";

    protected function setUp(): void
    {
        ConfigHelper::clearCache();
    }

    public function testAmpersandInDescriptionIsEscapedOnce(): void
    {
        $html = $this->renderWith("Metadescription: IBC & The Work\n");

        $this->assertSame('IBC & The Work', MetaHelper::buildDescription(page('a'), site()));
        $this->assertStringContainsString('<meta name="description" content="IBC &amp; The Work">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="IBC &amp; The Work">', $html);
        $this->assertStringContainsString('<meta name="twitter:description" content="IBC &amp; The Work">', $html);
        $this->assertStringContainsString($this->json('description', 'IBC & The Work'), $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString($this->json(null, '&amp;'), $html);
    }

    public function testQuotesInDescriptionAreEscapedOnce(): void
    {
        $html = $this->renderWith("Metadescription: Say \"hi\" & 'bye'\n");

        $this->assertSame('Say "hi" & \'bye\'', MetaHelper::buildDescription(page('a'), site()));
        $this->assertStringContainsString('content="Say &quot;hi&quot; &amp; &#039;bye&#039;"', $html);
        $this->assertStringContainsString($this->json('description', 'Say "hi" & \'bye\''), $html);
        $this->assertStringNotContainsString('&amp;quot;', $html);
        $this->assertStringNotContainsString('&amp;#039;', $html);
    }

    public function testLessThanInDescriptionIsKeptAndEscapedOnce(): void
    {
        $html = $this->renderWith("Metadescription: 1 < 2 and 3<4\n");

        $this->assertSame('1 < 2 and 3<4', MetaHelper::buildDescription(page('a'), site()));
        $this->assertStringContainsString('<meta name="description" content="1 &lt; 2 and 3&lt;4">', $html);
        // JSON_HEX_TAG keeps "<" from opening a tag inside the script element
        $this->assertStringContainsString($this->json('description', '1 < 2 and 3<4'), $html);
        $this->assertStringNotContainsString('&amp;lt;', $html);
    }

    public function testStoredEntitiesAreDecodedBeforeEscaping(): void
    {
        $html = $this->renderWith("Metadescription: Foo&nbsp;Bar, A &amp; B, &lt;tag&gt;\n");

        $this->assertSame('Foo' . self::NBSP . 'Bar, A & B, <tag>', MetaHelper::buildDescription(page('a'), site()));
        $this->assertStringContainsString('content="Foo' . self::NBSP . 'Bar, A &amp; B, &lt;tag&gt;"', $html);
        $this->assertStringNotContainsString('&amp;nbsp;', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString('&amp;lt;', $html);
        $this->assertStringNotContainsString('nbsp', $html);
    }

    public function testMarkupInDescriptionBecomesPlainText(): void
    {
        $this->renderWith("Metadescription: A **bold** <em>word</em> & more\n");

        $this->assertSame('A bold word & more', MetaHelper::buildDescription(page('a'), site()));
    }

    public function testOgDescriptionAndSiteFallbackAreEscapedOnce(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site\n----\nMetadescription: Site & more\n",
            'a/default.txt' => "Title: A\n----\nOgdescription: Share & enjoy\n",
            'b/default.txt' => "Title: B\n",
        ]);
        ConfigHelper::clearCache();

        $a = $this->render($kirby->page('a'));
        $this->assertStringContainsString('<meta property="og:description" content="Share &amp; enjoy">', $a);
        $this->assertStringContainsString('<meta name="twitter:description" content="Share &amp; enjoy">', $a);

        $b = $this->render($kirby->page('b'));
        $this->assertStringContainsString('<meta name="description" content="Site &amp; more">', $b);
        $this->assertStringNotContainsString('&amp;amp;', $a . $b);
    }

    public function testLimitCountsDecodedCharacters(): void
    {
        $this->renderWith('Metadescription: ' . trim(str_repeat('A & B ', 20)) . "\n");

        $short = MetaHelper::buildDescription(page('a'), site(), 30);
        $this->assertLessThanOrEqual(30, mb_strlen($short));
        $this->assertStringStartsWith('A & B A & B', $short);
        $this->assertStringEndsWith(' …', $short);
        $this->assertStringNotContainsString('&amp;', $short);
    }

    public function testTitlesAreEscapedOnce(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Inner & Sunrise\n",
            'parent/default.txt' => "Title: Kirtan & Mantra\n",
            'parent/child/default.txt' => "Title: Child&nbsp;Page\n----\nMetatitle: Say \"hi\" & 1 < 2\n----\nOgtitle: Share &amp; enjoy\n",
        ]);
        ConfigHelper::clearCache();

        // Breadcrumbs list only parents with a template file
        mkdir($kirby->root('templates'), 0777, true);
        touch($kirby->root('templates') . '/default.php');

        $page = $kirby->page('parent/child');
        $html = $this->render($page);

        $this->assertSame('Say "hi" & 1 < 2 | Inner & Sunrise', MetaHelper::buildTitle($page, $kirby->site(), 'meta'));
        $this->assertSame('Share & enjoy | Inner & Sunrise', MetaHelper::buildTitle($page, $kirby->site(), 'og'));

        $this->assertStringContainsString('<title>Say &quot;hi&quot; &amp; 1 &lt; 2 | Inner &amp; Sunrise</title>', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Share &amp; enjoy | Inner &amp; Sunrise">', $html);
        $this->assertStringContainsString('<meta name="twitter:title" content="Share &amp; enjoy | Inner &amp; Sunrise">', $html);
        $this->assertStringContainsString('<meta property="og:site_name" content="Inner &amp; Sunrise">', $html);

        // JSON-LD: WebPage name and breadcrumb names
        $this->assertStringContainsString($this->json('name', 'Say "hi" & 1 < 2 | Inner & Sunrise'), $html);
        $this->assertStringContainsString($this->json('name', 'Kirtan & Mantra'), $html);
        $this->assertStringContainsString($this->json('name', 'Child' . self::NBSP . 'Page'), $html);

        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString($this->json(null, '&amp;'), $html);
        $this->assertStringNotContainsString('nbsp', $html);
    }

    public function testLlmsTxtKeepsPlainDescription(): void
    {
        $this->makeKirby([
            'site.txt' => "Title: Site & Co\n",
            '1_a/default.txt' => "Title: A &amp; B\n----\nMetadescription: 1 < 2 & more\n",
        ]);
        ConfigHelper::clearCache();

        $markdown = (new LlmsTxt(kirby()))->toMarkdown();

        $this->assertStringContainsString('# Site & Co', $markdown);
        $this->assertStringContainsString('[A & B](', $markdown);
        $this->assertStringContainsString(': 1 < 2 & more', $markdown);
    }

    /**
     * A JSON-LD fragment as the snippet writes it: '"key": "value"', or the
     * encoded value alone without quotes when $key is null
     */
    private function json(?string $key, string $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return $key === null ? trim($encoded, '"') : '"' . $key . '": ' . $encoded;
    }

    private function renderWith(string $fields): string
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site\n",
            'a/default.txt' => "Title: A\n----\n" . $fields,
        ]);
        ConfigHelper::clearCache();

        return $this->render($kirby->page('a'));
    }

    private function render($page): string
    {
        $site = site();
        ob_start();
        include __DIR__ . '/../snippets/seo.php';
        return ob_get_clean();
    }
}
