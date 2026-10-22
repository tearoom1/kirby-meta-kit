<?php

namespace TearoomOne\Tests;

use Kirby\Cms\Page;
use TearoomOne\ConfigHelper;

class BreadcrumbSchemaTest extends KirbyTestCase
{
    private function makeSite(array $options = [], array $templates = ['default', 'retreats', 'retreat']): \Kirby\Cms\App
    {
        $kirby = $this->makeKirby(
            [
                'site.txt' => "Title: Site\n",
                'home/default.txt' => "Title: Home\n",
                '1_retreats/retreats.txt' => "Title: Retreats\n",
                '1_retreats/1_abc/retreat-parent.txt' => "Title: ABC\n",
                '1_retreats/1_abc/2026/year.txt' => "Title: 2026\n",
                '1_retreats/1_abc/2026/1_spring/retreat.txt' => "Title: Spring Retreat\n",
            ],
            ['tearoom1.meta-kit' => $options]
        );

        mkdir($kirby->root('templates'), 0777, true);
        foreach ($templates as $template) {
            touch($kirby->root('templates') . '/' . $template . '.php');
        }
        ConfigHelper::clearCache();

        return $kirby;
    }

    private function breadcrumb(Page $page): array
    {
        $site = site();
        ob_start();
        include __DIR__ . '/../snippets/seo.php';
        $html = ob_get_clean();

        preg_match_all('!<script type="application/ld\+json">(.*?)</script>!s', $html, $matches);
        foreach ($matches[1] as $json) {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            if ($data['@type'] === 'BreadcrumbList') {
                return array_map(
                    fn ($item) => [$item['position'], $item['name'], $item['item']],
                    $data['itemListElement']
                );
            }
        }

        $this->fail('No BreadcrumbList in the output');
    }

    public function testParentsWithoutTemplateFileOrExcludedTemplateAreLeftOut(): void
    {
        // "retreat-parent" has no template file and "default" is removed, so
        // Kirby can't render it; "year" is excluded by the option
        $kirby = $this->makeSite(
            ['schema.breadcrumb.excludeTemplates' => ['Year']],
            ['retreats', 'retreat', 'year']
        );

        $this->assertSame([
            [1, 'Site', '/'],
            [2, 'Retreats', '/retreats'],
            [3, 'Spring Retreat', '/retreats/abc/2026/spring'],
        ], $this->breadcrumb($kirby->page('retreats/abc/2026/spring')));
    }

    public function testParentsFallingBackToTheDefaultTemplateStay(): void
    {
        $kirby = $this->makeSite();

        $names = array_column($this->breadcrumb($kirby->page('retreats/abc/2026/spring')), 1);

        $this->assertSame(['Site', 'Retreats', 'ABC', '2026', 'Spring Retreat'], $names);
    }

    public function testUnlistedParentsStay(): void
    {
        $kirby = $this->makeKirby([
            'site.txt' => "Title: Site\n",
            'about/default.txt' => "Title: About\n",
            'about/1_team/default.txt' => "Title: Team\n",
        ]);
        mkdir($kirby->root('templates'), 0777, true);
        touch($kirby->root('templates') . '/default.php');

        $this->assertSame(
            ['Site', 'About', 'Team'],
            array_column($this->breadcrumb($kirby->page('about/team')), 1)
        );
    }

    public function testBreadcrumbUsesTheUrlsOfPageModels(): void
    {
        Page::extendModels(['retreat' => ShortUrlRetreatPage::class]);

        try {
            $kirby = $this->makeSite(['schema.breadcrumb.excludeTemplates' => ['retreat-parent', 'year']]);
            $items = $this->breadcrumb($kirby->page('retreats/abc/2026/spring'));
        } finally {
            Page::$models = [];
        }

        $this->assertSame([3, 'Spring Retreat', '/r/spring'], $items[2]);
    }
}

/**
 * A model with a short URL, like a site that serves retreats at /r/<slug>
 */
class ShortUrlRetreatPage extends Page
{
    public function url($options = null): string
    {
        return '/r/' . $this->slug();
    }
}
