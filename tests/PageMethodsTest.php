<?php

namespace TearoomOne\Tests;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use TearoomOne\ConfigHelper;

/**
 * Loads the full plugin (index.php registers it globally), so every test
 * runs in its own process to keep the other tests plugin-free.
 */
class PageMethodsTest extends KirbyTestCase
{
    private function makePluginKirby(array $content)
    {
        require_once dirname(__DIR__) . '/index.php';

        return $this->makeKirby($content, ['tearoom1.meta-kit' => [
            'api.key' => 'test-key',
            'api.model' => 'test-model',
            // Unreachable: the tested paths must not call the API
            'api.endpoint' => 'http://127.0.0.1:9/never-called',
        ]]);
    }

    #[RunInSeparateProcess]
    public function testDeprecatedSettingsAliasStillWorks(): void
    {
        $this->makePluginKirby(['site.txt' => 'Title: Site']);

        $this->assertSame(ConfigHelper::getAiSettings(), ConfigHelper::getOpenRouterSettings());
    }
}
