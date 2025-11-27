<?php

use Kirby\Http\Response;
use TearoomOne\Robots;

return function () {
    $robots = new Robots(kirby());
    $content = $robots->generate();

    return new Response($content, 'text/plain', 200, [
        'Cache-Control' => 'max-age=3600, public',
    ]);
};

