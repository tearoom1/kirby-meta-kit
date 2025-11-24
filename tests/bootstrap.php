<?php

// Load Composer autoloader from the plugin
require_once __DIR__ . '/../vendor/autoload.php';

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
