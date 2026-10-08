<?php

use Kirby\Http\Response;
use TearoomOne\LlmsTxt;

return function () {
    if (!LlmsTxt::isEnabled()) {
        // Let Kirby render its regular error page
        return false;
    }

    return new Response(LlmsTxt::render(kirby()), 'text/markdown');
};
