<?php

/**
 * Meta Kit Frontend Routes
 *
 * Defines frontend routes (sitemap, license activation, etc.)
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
        "pattern" => "meta-kit/license/activate",
        "method" => "POST",
        "auth" => true,
        "action" => function () {
            $plugin = kirby()->plugin("tearoom1/meta-kit");
            return $plugin->license()->activate();
        },
    ],
];
