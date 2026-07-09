<?php

$pluginAutoload = __DIR__ . '/../vendor/autoload.php';
$pluginAutoloader = file_exists($pluginAutoload) ? require $pluginAutoload : null;

// Force PHPUnit and its companion packages to resolve from this plugin's
// vendor directory even if other local plugins register their own test
// autoloaders during the run.
if ($pluginAutoloader !== null) {
    $phpunitNamespaces = [
        'PHPUnit\\',
        'SebastianBergmann\\',
        'Theseer\\Tokenizer\\',
        'PharIo\\',
        'DeepCopy\\',
        'myclabs\\DeepCopy\\'
    ];

    spl_autoload_register(
        static function (string $class) use ($pluginAutoloader, $phpunitNamespaces): void {
            foreach ($phpunitNamespaces as $namespace) {
                if (str_starts_with($class, $namespace)) {
                    $pluginAutoloader->loadClass($class);
                    return;
                }
            }
        },
        true,
        true
    );
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'TearoomOne\\Tests\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Composer installs Kirby as a dev dependency. Keep the legacy project-level
// lookup as a fallback for contributors who have not installed plugin dev dependencies.
if (class_exists(\Kirby\Cms\App::class) === false) {
    $kirbyVendorAutoload = __DIR__ . '/../../../../vendor/autoload.php';

    if (file_exists($kirbyVendorAutoload)) {
        require_once $kirbyVendorAutoload;
    } else {
        $kirbyVendorAutoload = __DIR__ . '/../../../../kirby/vendor/autoload.php';
        if (file_exists($kirbyVendorAutoload)) {
            require_once $kirbyVendorAutoload;
        } else {
            throw new Exception('Kirby autoloader not found. Run composer install.');
        }
    }
}

\Kirby\Cms\App::$enableWhoops = false;

// Disable Kirby's default error handling for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
