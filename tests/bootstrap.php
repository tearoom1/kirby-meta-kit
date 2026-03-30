<?php

/** @var \Composer\Autoload\ClassLoader $pluginAutoloader */
$pluginAutoloader = require __DIR__ . '/../vendor/autoload.php';

// Force PHPUnit and its companion packages to resolve from this plugin's
// vendor directory even if other local plugins register their own test
// autoloaders during the run.
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

// Load Kirby's autoloader directly (bypassing version check for tests)
// Path: tests -> meta-kit -> plugins -> site -> project root -> vendor
$kirbyVendorAutoload = __DIR__ . '/../../../../vendor/autoload.php';

if (file_exists($kirbyVendorAutoload)) {
    require_once $kirbyVendorAutoload;
} else {
    // Try alternative path: tests -> meta-kit -> plugins -> site -> project root -> kirby -> vendor
    $kirbyVendorAutoload = __DIR__ . '/../../../../kirby/vendor/autoload.php';
    if (file_exists($kirbyVendorAutoload)) {
        require_once $kirbyVendorAutoload;
    } else {
        throw new Exception('Kirby autoloader not found. Please ensure Kirby is installed.');
    }
}

// Disable Kirby's default error handling for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
