<?php

use Kirby\Http\Response;

return function () {
    $file = __DIR__ . '/../../sitemap.xsl';
    if (file_exists($file)) {
        return new Response(file_get_contents($file), 'application/xslt+xml', 200, [
            'Content-Type' => 'application/xslt+xml; charset=utf-8'
        ]);
    }
    return new Response('XSL file not found', 'text/plain', 404);
};
