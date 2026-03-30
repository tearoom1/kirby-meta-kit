<?php

namespace TearoomOne\Tests;

use Kirby\Cms\App as KirbyApp;
use PHPUnit\Framework\TestCase;
use TearoomOne\ConfigHelper;
use TearoomOne\MetaKit;

/**
 * Base test case that handles Kirby instance lifecycle, static cache resets,
 * and provides shared helpers for all Meta Kit test classes.
 */
abstract class KirbyTestCase extends TestCase
{
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->deleteDirectory($dir);
        }
        $this->tempDirs = [];

        // Reset Kirby singleton
        $reflection = new \ReflectionClass(KirbyApp::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, null);

        // Reset MetaKit AI enabled static cache
        $reflection = new \ReflectionClass(MetaKit::class);
        $property = $reflection->getProperty('aiEnabledCache');
        $property->setValue(null, null);

        // Reset ConfigHelper site settings cache
        ConfigHelper::clearCache();
    }

    /**
     * Spin up a minimal Kirby instance backed by temporary content files.
     *
     * @param array<string, string> $contentFiles  Relative paths under /content → file contents
     * @param array<string, mixed>  $options        Kirby plugin options (tearoom1.meta-kit.*)
     * @param array<int, array>|null $languages     Language definitions for multilang tests
     */
    protected function makeKirby(
        array $contentFiles,
        array $options = [],
        ?array $languages = null
    ): KirbyApp {
        $testDir = sys_get_temp_dir() . '/kirby-test-' . uniqid();
        $this->tempDirs[] = $testDir;
        mkdir($testDir, 0777, true);
        mkdir($testDir . '/content', 0777, true);

        foreach ($contentFiles as $relativePath => $content) {
            $fullPath = $testDir . '/content/' . $relativePath;
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($fullPath, $content);
        }

        $config = [
            'roots' => [
                'index'   => $testDir,
                'content' => $testDir . '/content',
            ],
            'options' => $options,
            'users' => [
                [
                    'email' => 'test@test.com',
                    'role'  => 'admin',
                ],
            ],
            'user' => 'test@test.com',
        ];

        if ($languages !== null) {
            $config['languages'] = $languages;
        }

        $kirby = new KirbyApp($config);
        $this->removeForeignPluginAutoloaders();
        $kirby->impersonate('test@test.com');
        return $kirby;
    }

    /**
     * Build the JSON string that Kirby stores in a blocks content field for a
     * single block, matching the format written by the panel.
     *
     * Field keys in $content should be lowercase (as Kirby stores them).
     */
    protected function makeBlocksJson(string $type, array $content, string $id = 'test-id'): string
    {
        return json_encode([[
            'content'  => $content,
            'id'       => $id,
            'isHidden' => false,
            'type'     => $type,
        ]]);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Some neighboring plugins in the same workspace ship their own PHPUnit
     * versions and register Composer autoloaders when Kirby boots. Remove those
     * foreign plugin autoloaders so the suite stays bound to Meta Kit's own
     * PHPUnit dependency graph.
     */
    private function removeForeignPluginAutoloaders(): void
    {
        $currentPluginVendor = realpath(__DIR__ . '/../vendor');

        foreach (spl_autoload_functions() as $autoload) {
            if (
                is_array($autoload) !== true ||
                is_object($autoload[0]) !== true ||
                $autoload[1] !== 'loadClass'
            ) {
                continue;
            }

            $loader = $autoload[0];
            if ($loader instanceof \Composer\Autoload\ClassLoader !== true) {
                continue;
            }

            $reflection = new \ReflectionObject($loader);
            if ($reflection->hasProperty('vendorDir') !== true) {
                continue;
            }

            $vendorDir = realpath($reflection->getProperty('vendorDir')->getValue($loader)) ?: '';

            if (
                $vendorDir !== '' &&
                str_contains($vendorDir, '/site/plugins/') === true &&
                $vendorDir !== $currentPluginVendor
            ) {
                spl_autoload_unregister($autoload);
            }
        }
    }
}
