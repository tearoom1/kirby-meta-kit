<?php

namespace TearoomOne\Tests;

use TearoomOne\ConfigHelper;
use TearoomOne\MetaHelper;

class InheritanceTest extends KirbyTestCase
{
    private function makeSite(array $options = []): \Kirby\Cms\App
    {
        $kirby = $this->makeKirby(
            [
                'site.txt' => "Title: Site\n\n----\n\nOgimage: - site.png\n",
                'site.png' => 'x',
                '1_section/default.txt' => "Title: Section\n\n----\n\nOgimage: - section.png\n",
                '1_section/section.png' => 'x',
                '1_section/1_child/default.txt' => "Title: Child\n",
                '1_section/1_child/1_grandchild/default.txt' => "Title: Grandchild\n",
                '1_section/2_own/default.txt' => "Title: Own\n\n----\n\nOgimage: - own.png\n",
                '1_section/2_own/own.png' => 'x',
                '2_plain/default.txt' => "Title: Plain\n",
                '3_hidden/default.txt' => "Title: Hidden\n\n----\n\nRobots: noindex, nofollow\n",
                '3_hidden/1_below/default.txt' => "Title: Below\n",
            ],
            ['tearoom1.meta-kit' => $options]
        );
        ConfigHelper::clearCache();

        return $kirby;
    }

    private function fileFor(string $id): ?string
    {
        return MetaHelper::ogImageFile(page($id), site())?->filename();
    }

    public function testChildrenTakeTheSiteImageByDefault(): void
    {
        $this->makeSite();

        $this->assertSame('section.png', $this->fileFor('section'));
        $this->assertSame('site.png', $this->fileFor('section/child'));
    }

    public function testChildrenInheritTheNearestParentWhenTheOptionIsOn(): void
    {
        $this->makeSite(['inherit' => ['ogImage']]);

        $this->assertSame('section.png', $this->fileFor('section/child'));
        $this->assertSame('section.png', $this->fileFor('section/child/grandchild'));
    }

    public function testAPageWithItsOwnImageKeepsIt(): void
    {
        $this->makeSite(['inherit' => ['ogImage']]);

        $this->assertSame('own.png', $this->fileFor('section/own'));
    }

    public function testAPageWithoutParentImageFallsBackToTheSite(): void
    {
        $this->makeSite(['inherit' => ['ogImage']]);

        $this->assertSame('site.png', $this->fileFor('plain'));
    }

    public function testOtherFieldsStayOnThePageUnlessNamed(): void
    {
        $this->makeSite(['inherit' => ['ogImage']]);

        $this->assertTrue(MetaHelper::inherited(page('hidden/below'), 'robots')->isEmpty());
    }

    public function testRobotsCanBeInherited(): void
    {
        $this->makeSite(['inherit' => ['robots']]);

        $this->assertSame('noindex, nofollow', MetaHelper::inherited(page('hidden/below'), 'robots')->value());
    }
}
