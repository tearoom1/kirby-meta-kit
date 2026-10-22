<?php

use Kirby\Http\Response;

/**
 * /sitemap redirects permanently to /sitemap.xml while the sitemap is enabled.
 * Otherwise the request falls through, so a page with the slug "sitemap" still works.
 */
return function () {
    if (option('tearoom1.meta-kit.sitemap.enabled', true) === false) {
        return $this->next();
    }

    return Response::redirect(url('sitemap.xml'), 301);
};
