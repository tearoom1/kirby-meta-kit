<?php

namespace TearoomOne;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;

/**
 * /llms.txt (https://llmstxt.org): a Markdown overview of the site for AI
 * assistants, listing the same pages as the sitemap
 */
class LlmsTxt
{
    public function __construct(protected Kirby $kirby)
    {
    }

    /**
     * Enabled in the robots.txt panel settings or via `llms.enabled`
     */
    public static function isEnabled(): bool
    {
        $configured = option('tearoom1.meta-kit.llms.enabled');
        if ($configured !== null) {
            return (bool)$configured;
        }

        $robots = MetaHelper::getSeoData(kirby()->site()->metaKitRobots());
        return $robots ? $robots->llmsTxt()->toBool() : false;
    }

    /**
     * Markdown from the plugin cache when possible (shared with the sitemap
     * and flushed by the same hooks)
     */
    public static function render(Kirby $kirby): string
    {
        $cache = $kirby->cache('tearoom1.meta-kit.sitemap');

        if (($text = $cache->get('llms')) !== null) {
            return $text;
        }

        $text = (new static($kirby))->toMarkdown();
        $cache->set('llms', $text, (int)option('tearoom1.meta-kit.sitemap.cacheDuration', 60));

        return $text;
    }

    public function toMarkdown(): string
    {
        $site = $this->kirby->site();
        $summary = ConfigHelper::getSiteSettings()['siteMetaDescription'] ?? '';

        $lines = ['# ' . $this->clean($site->title()->value())];
        if ($summary !== '') {
            $lines[] = '';
            $lines[] = '> ' . $this->clean($summary);
        }

        // Top-level pages without included children are listed together,
        // every other top-level page gets its own section
        $pages = (new Sitemap($this->kirby))->pages();
        $sections = [];
        foreach ($pages as $page) {
            $root = $page->parents()->last() ?? $page;
            $sections[$root->id()][] = $page;
        }

        $general = [];
        foreach ($sections as $rootId => $sectionPages) {
            if (count($sectionPages) === 1) {
                $general[] = $sectionPages[0];
                continue;
            }

            $lines[] = '';
            $lines[] = '## ' . $this->clean($this->kirby->page($rootId)?->title()->value() ?? $rootId);
            $lines[] = '';
            foreach ($sectionPages as $page) {
                $lines[] = $this->entry($page);
            }
        }

        if ($general !== []) {
            array_splice($lines, $summary !== '' ? 3 : 1, 0, ['', '## Pages', '', ...array_map(fn ($page) => $this->entry($page), $general)]);
        }

        return implode("\n", $lines) . "\n";
    }

    protected function entry(Page $page): string
    {
        $title = $page->metaTitle()->isNotEmpty() ? $page->metaTitle()->value() : $page->title()->value();
        $line = '- [' . $this->clean($title) . '](' . $page->url() . ')';

        $description = MetaHelper::buildDescription($page, $this->kirby->site());
        if ($description !== '' && $page->metaDescription()->isNotEmpty()) {
            $line .= ': ' . $this->clean($description);
        }

        return $line;
    }

    /**
     * One line of plain text, without characters that break Markdown links
     */
    protected function clean(string $text): string
    {
        return trim(str_replace(['[', ']', "\n", "\r"], ['(', ')', ' ', ' '], strip_tags($text)));
    }
}
