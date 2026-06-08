<?php

/**
 * Meta Kit Frontend Routes
 *
 * Defines frontend routes (sitemap, robots.txt, etc.)
 */

return [
    [
        "pattern" => "sitemap.xsl",
        "action" => require __DIR__ . "/sitemap-xsl.php",
    ],
    [
        "pattern" => "sitemap.xml",
        "action" => require __DIR__ . "/sitemap.php",
    ],
    [
        "pattern" => "robots.txt",
        "action" => require __DIR__ . "/robots.txt.php",
    ],
];
