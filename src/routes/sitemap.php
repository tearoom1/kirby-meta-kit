<?php

use TearoomOne\Sitemap;
use Kirby\Http\Response;

return function () {
    // Check if sitemap is enabled in config
    if (option('tearoom1.meta-kit.sitemap.enabled', true) === false) {
        return new Response('Sitemap is disabled', 'text/plain', 404);
    }

    $sitemapGenerator = new Sitemap(kirby());
    $sitemap = $sitemapGenerator->generate();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n";

    // Check if multilanguage site
    if (kirby()->multilang()) {
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
    } else {
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    }

    foreach ($sitemap as $item) {
        $xml .= "\n    <url>";
        $xml .= "\n        <loc>" . htmlspecialchars($item['url']) . "</loc>";
        $xml .= "\n        <lastmod>" . $item['lastmod'] . "</lastmod>";
        $xml .= "\n        <changefreq>" . $item['changefreq'] . "</changefreq>";
        $xml .= "\n        <priority>" . $item['priority'] . "</priority>";

        // Add alternate language links
        if (!empty($item['alternates'])) {
            foreach ($item['alternates'] as $alternate) {
                $xml .= "\n        <xhtml:link rel=\"alternate\" hreflang=\"" .
                       htmlspecialchars($alternate['lang']) . "\" href=\"" .
                       htmlspecialchars($alternate['url']) . "\" />";
            }
        }

        $xml .= "\n    </url>";
    }

    $xml .= "\n</urlset>";

    return new Response($xml, 'application/xml');
};
