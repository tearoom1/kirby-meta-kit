<?php

namespace TearoomOne\Tests;

use Kirby\Http\Response;
use TearoomOne\Redirects;

class RedirectsTest extends KirbyTestCase
{
    private function site(array $options = [], ?array $languages = null)
    {
        $content = $languages
            ? [
                'site.en.txt' => 'Title: Site',
                '1_journal/default.en.txt' => 'Title: Journal',
                '1_journal/1_old-post/default.en.txt' => 'Title: Post',
                '1_journal/1_old-post/1_comments/default.en.txt' => 'Title: Comments',
            ]
            : [
                'site.txt' => 'Title: Site',
                '1_journal/default.txt' => 'Title: Journal',
                '1_journal/1_old-post/default.txt' => 'Title: Post',
                '1_journal/1_old-post/1_comments/default.txt' => 'Title: Comments',
            ];

        return $this->makeKirby($content, $options, $languages);
    }

    private function rename(string $id, string $slug, ?string $language = null)
    {
        $old = kirby()->page($id);
        $new = $old->changeSlug($slug, $language);
        Redirects::record($new, $old);
        return $new;
    }

    public function testOldUrlAndSubpagesRedirectToTheRenamedPage(): void
    {
        $kirby = $this->site();
        $this->rename('journal/old-post', 'new-post');

        $this->assertSame($kirby->page('journal/new-post')->url(), Redirects::find('/journal/old-post'));
        $this->assertSame($kirby->page('journal/new-post')->url() . '/comments', Redirects::find('journal/old-post/comments'));
        $this->assertNull(Redirects::find('/journal/old-post-2'));

        $entry = Redirects::entries()[0];
        $this->assertSame('/journal/old-post', $entry['from']);
        $this->assertStringStartsWith('page://', $entry['to'][0]);
    }

    public function testRenamingAgainKeepsAllOldUrlsWorkingWithoutChains(): void
    {
        $kirby = $this->site();
        $this->rename('journal/old-post', 'second');
        $this->rename('journal/second', 'third');

        $current = $kirby->page('journal/third')->url();
        $this->assertSame($current, Redirects::find('/journal/old-post'));
        $this->assertSame($current, Redirects::find('/journal/second'));
    }

    public function testRenamingBackRemovesTheRedirectForTheNowUsedUrl(): void
    {
        $this->site();
        $this->rename('journal/old-post', 'new-post');
        $this->rename('journal/new-post', 'old-post');

        $this->assertNull(Redirects::find('/journal/old-post'));
        $this->assertSame(['/journal/new-post'], array_column(Redirects::entries(), 'from'));
    }

    public function testCanBeDisabled(): void
    {
        $this->site(['tearoom1.meta-kit' => ['redirects.enabled' => false]]);
        $this->rename('journal/old-post', 'new-post');

        $this->assertSame([], Redirects::entries());
    }

    public function testOnlyTheLanguageWithTheNewSlugGetsARedirect(): void
    {
        $kirby = $this->site([], [
            ['code' => 'en', 'default' => true, 'url' => '/en'],
            ['code' => 'de', 'url' => '/de'],
        ]);
        $this->rename('journal/old-post', 'neuer-beitrag', 'de');

        $this->assertSame(['/de/journal/old-post'], array_column(Redirects::entries(), 'from'));
        $this->assertSame($kirby->page('journal/old-post')->url('de'), Redirects::find('/de/journal/old-post'));
        $this->assertStringEndsWith('/de/journal/neuer-beitrag', Redirects::find('/de/journal/old-post'));
    }

    public function testRouteHookRedirectsOnlyWhenNoPageWasFound(): void
    {
        $kirby = $this->site();
        $this->rename('journal/old-post', 'new-post');
        $hooks = require dirname(__DIR__) . '/src/hooks.php';
        $hook = $hooks['route:after'];

        $kirby = $kirby->clone(['request' => ['url' => 'https://example.test/journal/old-post']]);
        $response = $hook(null, 'journal/old-post', 'GET', null, true);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(301, $response->code());
        $this->assertSame($kirby->page('journal/new-post')->url(), $response->header('Location'));

        // A found page, a non-final route or a POST request stay untouched
        $page = $kirby->page('journal');
        $this->assertSame($page, $hook(null, 'journal/old-post', 'GET', $page, true));
        $this->assertNull($hook(null, 'journal/old-post', 'GET', null, false));
        $this->assertNull($hook(null, 'journal/old-post', 'POST', null, true));
    }
}
