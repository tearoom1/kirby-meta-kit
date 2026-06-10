<?php
/**
 * Unified SEO snippet - Meta tags, OpenGraph, and Schema.org
 * All fields are now flat fields on both pages and site
 */

use TearoomOne\MetaHelper;

$site = $site ?? site();
$page = $page ?? page();

// Get configuration options
$enableMeta = option('tearoom1.meta-kit.meta.enabled', true);
$enableOpengraph = option('tearoom1.meta-kit.opengraph.enabled', true);
$enableSchema = option('tearoom1.meta-kit.schema.enabled', true);

// ==============================================================
// Build Common Data
// ==============================================================

// Build meta title and description using helper (reads from flat page fields)
$metaTitle = MetaHelper::buildTitle($page, $site, 'meta');
$metaDescription = MetaHelper::buildDescription($page, $site);

// Get canonical URL (flat field on page)
if ($page->canonicalUrl()->isNotEmpty()) {
    $canonical = $page->canonicalUrl()->value();
} else {
    $canonical = $page->url();
}

// Get robots directive (flat field on page, fallback to site flat field)
$robots = $page->robots()->isNotEmpty() ? $page->robots()->value() : $site->robots()->value();
$keywords = $page->metaKeywords()->isNotEmpty() ? $page->metaKeywords() : null;
$author = $page->metaAuthor()->isNotEmpty() ? $page->metaAuthor() : ($site->metaAuthor()->isNotEmpty() ? $site->metaAuthor() : null);

// Get OG title (use custom OG title or fall back to meta title)
$ogTitle = MetaHelper::buildTitle($page, $site, 'og');

// Get OG description using helper
$ogDescription = MetaHelper::buildOgDescription($page, $site, $metaDescription);

// Get OG image (flat field on page, fallback to site flat field)
$ogImage = null;
if ($page->ogImage()->isNotEmpty()) {
    $ogImageFile = $page->ogImage()->toFile();
    if ($ogImageFile) {
        $ogImage = $ogImageFile->resize(1200, 630);
    }
} elseif ($site->ogImage()->isNotEmpty()) {
    $ogImageFile = $site->ogImage()->toFile();
    if ($ogImageFile) {
        $ogImage = $ogImageFile->resize(1200, 630);
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
<?php if ($robots !== null && $robots !== 'index, follow' && strlen($robots) > 1): ?>
    <meta name="robots" content="<?= esc($robots) ?>">
<?php endif; ?>

    <!-- Additional meta tags -->
<?php if ($keywords): ?>
    <meta name="keywords" content="<?= $keywords->html() ?>">
<?php endif; ?>
<?php if ($author): ?>
    <meta name="author" content="<?= esc($author) ?>">
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
if ($site->metaKitSocialSites()->isNotEmpty()) {
    $socialProfiles = [];
    foreach ($site->metaKitSocialSites()->toStructure() as $social) {
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
    '@type' => 'WebPage',
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
