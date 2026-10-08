<?php

namespace TearoomOne\Tests;

class HooksTest extends KirbyTestCase
{
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
}
