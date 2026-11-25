<?php

namespace TearoomOne\Tests;

use Kirby\Form\Field;
use Kirby\Form\Form;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use TearoomOne\Fields\DescriptionField;
use TearoomOne\Fields\PreviewField;
use TearoomOne\Fields\ReviewField;
use TearoomOne\Fields\SlugInfoField;
use TearoomOne\Fields\TitleField;

/**
 * Kirby 6 needs field classes; the preview is a field instead of a section.
 * Uses the registered plugin, so every test runs in its own process.
 */
class FieldClassesTest extends KirbyTestCase
{
    private function makeSite(array $pageBlueprint)
    {
        require_once dirname(__DIR__) . '/index.php';

        return $this->makeKirby([
            'site.txt' => "Title: Site\n----\nAppendsitename: true\n----\nAppendsitenameto: meta\n----\nTitleseparator: |\n",
            'article/article.txt' => "Title: Article\n----\nMetatitle: Saved title\n",
        ], extraConfig: ['blueprints' => ['pages/article' => $pageBlueprint]]);
    }

    #[RunInSeparateProcess]
    public function testFieldTypesAreRegisteredAsClasses(): void
    {
        $this->makeSite(['fields' => ['text' => ['type' => 'text']]]);

        $this->assertSame(TitleField::class, Field::resolve('mk-title'));
        $this->assertSame(DescriptionField::class, Field::resolve('mk-description'));
        $this->assertSame(SlugInfoField::class, Field::resolve('mk-slug-info'));
        $this->assertSame(ReviewField::class, Field::resolve('mk-review'));
        $this->assertSame(PreviewField::class, Field::resolve('seo-preview'));
    }

    #[RunInSeparateProcess]
    public function testShippedPageBlueprintBuildsEveryField(): void
    {
        $kirby = $this->makeSite(['tabs' => ['seo' => ['extends' => 'meta-kit/page']]]);
        $fields = Form::for($kirby->page('article'))->fields();

        $this->assertInstanceOf(TitleField::class, $fields->get('metatitle'));
        $this->assertInstanceOf(DescriptionField::class, $fields->get('metadescription'));
        $this->assertInstanceOf(SlugInfoField::class, $fields->get('sluginfo'));
        $this->assertInstanceOf(PreviewField::class, $fields->get('seopreview'));

        // No field fell back to Kirby's error field
        foreach ($fields as $field) {
            $this->assertNotSame('negative', $field->toArray()['theme'] ?? null, $field->name());
        }

        $title = $fields->get('metatitle')->toArray();
        $this->assertSame('article', $title['validationSettings']['pageId']);
        $this->assertSame('article', $title['validationSettings']['template']);
        $this->assertSame('og', $fields->get('ogtitle')->toArray()['fieldType']);
        $this->assertSame('article', $fields->get('sluginfo')->toArray()['currentSlug']);
        $this->assertFalse($fields->get('sluginfo')->toArray()['saveable']);
        $this->assertTrue($fields->get('metatitle')->toArray()['saveable']);
    }

    /**
     * Blueprints written for Meta Kit 4 list the preview under `sections`
     */
    #[RunInSeparateProcess]
    public function testPreviewStillWorksAsSection(): void
    {
        $kirby = $this->makeSite(['sections' => [
            'preview' => ['type' => 'seo-preview', 'label' => 'Preview'],
        ]]);

        $field = Form::for($kirby->page('article'))->fields()->get('preview');

        $this->assertInstanceOf(PreviewField::class, $field);
        $this->assertSame('Preview', $field->toArray()['label']);
    }

    #[RunInSeparateProcess]
    public function testPreviewShowsUnsavedChanges(): void
    {
        $kirby = $this->makeSite(['fields' => [
            'metaTitle' => ['type' => 'mk-title'],
            'preview' => ['type' => 'seo-preview'],
        ]]);

        $page = $kirby->page('article');
        $page->version('changes')->save(['metaTitle' => 'Unsaved title']);

        $meta = Form::for($kirby->page('article'))->fields()->get('preview')->toArray()['meta'];

        $this->assertSame('Unsaved title | Site', $meta['title']);
    }

    #[RunInSeparateProcess]
    public function testReviewFieldProps(): void
    {
        $kirby = $this->makeSite(['fields' => ['review' => [
            'type' => 'mk-review',
            'label' => 'meta-kit.bp.meta-group.seoReview.label',
        ]]]);

        $review = Form::for($kirby->page('article'))->fields()->get('review')->toArray();

        $this->assertSame('AI Content Review', $review['label']);
        $this->assertSame('info', $review['theme']);
        $this->assertSame('article', $review['pageId']);
    }
}
