<?php
/**
 * Unified SEO snippet - Meta tags, OpenGraph, and Schema.org
 */
$site = $site ?? site();
$page = $page ?? page();

// Get configuration options
$enableMeta = option('tearoom1.meta-kit.meta.enabled', true);
$enableOpengraph = option('tearoom1.meta-kit.opengraph.enabled', true);
$enableSchema = option('tearoom1.meta-kit.schema.enabled', true);

// Get SEO data from object field
$seoData = $page->metaKitSeo()->toObject();

// ==============================================================
// Build Common Data
// ==============================================================

// Build meta title and description using helper
$metaTitle = \TearoomOne\MetaHelper::buildTitle($page, $site, $seoData);
$metaDescription = \TearoomOne\MetaHelper::buildDescription($page, $site, $seoData);

// Get canonical URL
if ($seoData && $seoData->canonicalUrl()->isNotEmpty()) {
    $canonical = $seoData->canonicalUrl()->value();
} else {
    $canonical = $page->url();
}

// Check noIndex
$noIndex = $seoData && $seoData->noIndex()->isTrue();

// Get OG title (use custom OG title or fall back to meta title)
if ($seoData && $seoData->ogTitle()->isNotEmpty()) {
    $ogTitle = \TearoomOne\MetaHelper::buildTitle($page, $site, $seoData);
} else {
    $ogTitle = $metaTitle;
}

// Get OG description using helper
$ogDescription = \TearoomOne\MetaHelper::buildOgDescription($page, $site, $seoData, $metaDescription);

// Get OG image
$ogImage = null;
if ($seoData && $seoData->ogImage()->isNotEmpty()) {
    $ogImageFile = $seoData->ogImage()->toFile();
    if ($ogImageFile) {
        $ogImage = $ogImageFile->crop(1200, 630);
    }
} else {
    $siteSeo = $site->metaKitSeo()->toObject();
    if ($siteSeo && $siteSeo->ogImage()->isNotEmpty()) {
        $ogImageFile = $siteSeo->ogImage()->toFile();
        if ($ogImageFile) {
            $ogImage = $ogImageFile->crop(1200, 630);
        }
    }
}
?>

<?php if ($enableMeta): ?>
<!-- SEO Meta Tags -->
<title><?= esc($metaTitle) ?></title>
<meta name="description" content="<?= esc($metaDescription) ?>">
<link rel="canonical" href="<?= esc($canonical) ?>">

<!-- Alternate language versions -->
<?php if (kirby()->multilang()): ?>
    <?php foreach (kirby()->languages() as $language): ?>
        <link rel="alternate" hreflang="<?= $language->code() ?>" href="<?= $page->url($language->code()) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= $page->url(kirby()->defaultLanguage()->code()) ?>">
<?php endif; ?>

<!-- No index if needed -->
<?php if ($noIndex): ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif; ?>

<!-- Additional meta tags -->
<?php if ($seoData && $seoData->metaKeywords()->isNotEmpty()): ?>
    <meta name="keywords" content="<?= $seoData->metaKeywords()->html() ?>">
<?php endif; ?>

<?php endif; ?>

<?php if ($enableOpengraph): ?>
<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $page->url() ?>">
<meta property="og:title" content="<?= esc($ogTitle) ?>">
<?php if (!empty($ogDescription)): ?>
<meta property="og:description" content="<?= esc($ogDescription) ?>">
<?php endif; ?>
<?php if ($ogImage): ?>
<meta property="og:image" content="<?= $ogImage->url() ?>">
<meta property="og:image:width" content="<?= $ogImage->width() ?>">
<meta property="og:image:height" content="<?= $ogImage->height() ?>">
<?php endif; ?>
<?php if (kirby()->multilang()): ?>
<meta property="og:locale" content="<?= str_replace('-', '_', kirby()->language()->code()) ?>">
    <?php foreach (kirby()->languages() as $language): ?>
        <?php if ($language->code() !== kirby()->language()->code()): ?>
<meta property="og:locale:alternate" content="<?= str_replace('-', '_', $language->code()) ?>">
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= esc($ogTitle) ?>">
<?php if (!empty($ogDescription)): ?>
<meta name="twitter:description" content="<?= esc($ogDescription) ?>">
<?php endif; ?>
<?php if ($ogImage): ?>
<meta name="twitter:image" content="<?= $ogImage->url() ?>">
<?php endif; ?>

<?php endif; ?>

<?php if ($enableSchema): ?>
<?php
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
    'name' => $metaTitle,
    'description' => $metaDescription,
    'url' => $page->url(),
    'inLanguage' => $site->language() ? $site->language()->code() : 'en',
    'isPartOf' => [
        '@type' => 'WebSite',
        'url' => $site->url(),
        'name' => $site->title()->value(),
    ],
];

// Add image if available
if ($ogImage) {
    $webPageSchema['image'] = $ogImage->url();
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

<?php endif; ?>
