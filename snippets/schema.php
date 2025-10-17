<?php
/**
 * Schema.org JSON-LD structured data
 */
$site = $site ?? site();
$page = $page ?? page();

// Check if schema is enabled in config
if (option('tearoom1.meta-stuff.schema.enabled', true) === false) {
    return;
}
$seoData = $page->seo()->toObject();

// Get site settings
$separator = $site->titleSeparator()->or('|')->value();
$appendSiteName = $site->appendSiteName()->or('true')->toBool();

// Build page title
if ($seoData && $seoData->metaTitle()->isNotEmpty()) {
    $pageTitle = $seoData->metaTitle()->value();
} else {
    $pageTitle = $page->title()->value();
}

if ($appendSiteName && !str_contains($pageTitle, $site->title()->value())) {
    $pageTitle = $pageTitle . ' ' . $separator . ' ' . $site->title()->value();
}

// Get description
if ($seoData && $seoData->metaDescription()->isNotEmpty()) {
    $description = $seoData->metaDescription()->value();
} else {
    $description = $page->text()->excerpt(160);
}

// Get image
$image = null;
if ($seoData && $seoData->ogImage()->isNotEmpty()) {
    $files = $seoData->ogImage()->toFiles();
    if ($files && $files->count() > 0) {
        $imageFile = $files->first();
        // Resize to OG dimensions (1200x630)
        $image = $imageFile ? $imageFile->crop(1200, 630)->url() : null;
    }
}

// Fallback to site default OG image
if (!$image && $site->ogImage()->isNotEmpty()) {
    $siteImage = $site->ogImage()->toFile();
    // Resize to OG dimensions (1200x630)
    $image = $siteImage ? $siteImage->crop(1200, 630)->url() : null;
}

// Organization Schema
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $site->title()->value(),
    'url' => $site->url(),
];

// Add logo if available
if ($site->ogImage()->isNotEmpty()) {
    $logo = $site->ogImage()->toFile();
    if ($logo) {
        // Resize logo to reasonable dimensions (600x600 square for logo)
        $organizationSchema['logo'] = $logo->crop(600, 600)->url();
    }
}

// Add social profiles if available
if ($site->socialSites()->isNotEmpty()) {
    $socialProfiles = [];
    foreach ($site->socialSites()->toStructure() as $social) {
        if ($social->url()->isNotEmpty()) {
            $socialProfiles[] = $social->url()->value();
        }
    }
    if (!empty($socialProfiles)) {
        $organizationSchema['sameAs'] = $socialProfiles;
    }
}

// WebSite Schema
$websiteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $site->title()->value(),
    'url' => $site->url(),
    'publisher' => [
        '@type' => 'Organization',
        'name' => $site->title()->value(),
    ],
];

// Add search action
$websiteSchema['potentialAction'] = [
    '@type' => 'SearchAction',
    'target' => [
        '@type' => 'EntryPoint',
        'urlTemplate' => $site->url() . '/search?q={search_term_string}'
    ],
    'query-input' => 'required name=search_term_string'
];

// WebPage Schema
$webPageSchema = [
    '@context' => 'https://schema.org',
    '@type' => $page->isHomePage() ? 'WebPage' : 'WebPage',
    'name' => $pageTitle,
    'description' => $description,
    'url' => $page->url(),
    'inLanguage' => $site->language() ? $site->language()->code() : 'en',
    'isPartOf' => [
        '@type' => 'WebSite',
        'url' => $site->url(),
        'name' => $site->title()->value(),
    ],
];

// Add image if available
if ($image) {
    $webPageSchema['image'] = $image;
}

// Add breadcrumb
if (!$page->isHomePage() && $page->parents()->count() > 0) {
    $breadcrumbItems = [];
    $position = 1;
    
    // Add home
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => $site->title()->value(),
        'item' => $site->url(),
    ];
    
    // Add parents
    foreach ($page->parents()->flip() as $parent) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $parent->title()->value(),
            'item' => $parent->url(),
        ];
    }
    
    // Add current page
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'name' => $page->title()->value(),
        'item' => $page->url(),
    ];
    
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $breadcrumbItems,
    ];
}
?>

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
<?= json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<script type="application/ld+json">
<?= json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<script type="application/ld+json">
<?= json_encode($webPageSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<?php if (isset($breadcrumbSchema)): ?>
<script type="application/ld+json">
<?= json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>
