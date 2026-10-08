<?php

namespace TearoomOne\Tests;

use TearoomOne\MetaKit;

class HooksTest extends KirbyTestCase
{
    use MockApiServer;

    private array $deferred = [];

    protected function setUp(): void
    {
        $this->deferred = [];
        MetaKit::$deferHandler = function (\Closure $task) {
            $this->deferred[] = $task;
        };
        $this->resetAiEnabledCache();
    }

    protected function tearDown(): void
    {
        MetaKit::$deferHandler = null;
        $this->resetAiEnabledCache();
        parent::tearDown();
    }

    private function resetAiEnabledCache(): void
    {
        (new \ReflectionClass(MetaKit::class))->getProperty('aiEnabledCache')->setValue(null, null);
    }

    private function hook(string $name): \Closure
    {
        $hooks = require dirname(__DIR__) . '/src/hooks.php';
        return $hooks[$name];
    }

    public function testSettingsInitializationDoesNotKeepSuperuserImpersonation(): void
    {
        // No fixed 'user' in the config: Kirby would re-apply it on every
        // permission check and hide a leaked impersonation
        $kirby = $this->makeKirby(['site.txt' => 'Title: Test Site'], extraConfig: ['user' => null]);

        $this->hook('system.loadPlugins:after')();

        // The settings blocks were written…
        $this->assertTrue(site()->metaKitOpenrouter()->isNotEmpty());
        // …but the almighty "kirby" user must not outlive the update
        $this->assertNotSame('kirby', $kirby->auth()->currentUserFromImpersonation()?->id());
    }

    private function makeAutoGenerateKirby(string $content)
    {
        return $this->makeKirby(
            ['site.txt' => 'Title: Site', 'post/default.txt' => $content],
            ['tearoom1.meta-kit' => [
                'autoGenerate' => true,
                'api.key' => 'test-key',
                'api.model' => 'test-model',
                'api.endpoint' => self::$baseUrl . '/description',
            ]]
        );
    }

    public function testAutoGenerateRunsAfterTheResponse(): void
    {
        // Any field with enough text works, not only `text`
        $kirby = $this->makeAutoGenerateKirby(
            "Title: Post\n----\nBody: A sufficiently long paragraph about tea that easily passes the minimum length.\n"
        );
        $page = $kirby->page('post');

        $this->hook('page.update:after')($page, $page);

        // Nothing generated during the save itself…
        $this->assertCount(1, $this->deferred);
        $this->assertTrue($kirby->page('post')->metaDescription()->isEmpty());

        // …but once the deferred task runs
        ($this->deferred[0])();
        $this->assertStringStartsWith('Generated description for testing', $kirby->page('post')->metaDescription()->value());
    }

    public function testAutoGenerateSkipsPagesWithDescriptionOrTooLittleText(): void
    {
        $kirby = $this->makeAutoGenerateKirby(
            "Title: Post\n----\nBody: A sufficiently long paragraph about tea that easily passes the minimum length.\n----\nMetadescription: Already written\n"
        );
        $this->hook('page.update:after')($kirby->page('post'), $kirby->page('post'));

        $kirby = $this->makeAutoGenerateKirby("Title: Post\n----\nBody: Too short\n");
        $this->hook('page.update:after')($kirby->page('post'), $kirby->page('post'));

        $this->assertSame([], $this->deferred);
    }
}
