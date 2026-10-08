<?php

use TearoomOne\Sitemap;
use Kirby\Http\Response;

return function () {
    // Check if sitemap is enabled in config
    if (option('tearoom1.meta-kit.sitemap.enabled', true) === false) {
        return new Response('Sitemap is disabled', 'text/plain', 404);
    }

    return new Response(Sitemap::render(kirby()), 'application/xml');
};
