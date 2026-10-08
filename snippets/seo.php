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
$ogImageFile = $page->ogImage()->isNotEmpty()
    ? $page->ogImage()->toFile()
    : ($site->ogImage()->isNotEmpty() ? $site->ogImage()->toFile() : null);
if ($ogImageFile) {
    $ogImage = $ogImageFile->resize(1200, 630);
}
$ogImageAlt = $ogImageFile && $ogImageFile->alt()->isNotEmpty() ? $ogImageFile->alt()->value() : null;

// Articles (blog posts etc.) get og:type article, dates and Article schema
$isArticle = in_array(
    $page->intendedTemplate()->name(),
    (array)option('tearoom1.meta-kit.opengraph.articleTemplates', ['article', 'post']),
    true
);
$dateField = $page->content()->get(option('tearoom1.meta-kit.opengraph.dateField', 'date'));
// Via the timestamp: toDate() formats with the configured date.handler
// (e.g. intl), where "c" is not an ISO 8601 date
$publishedTimestamp = $isArticle && $dateField->isNotEmpty() ? $dateField->toTimestamp() : false;
$publishedTime = $publishedTimestamp ? date('c', $publishedTimestamp) : null;
$modifiedTime = $isArticle ? date('c', $page->modified()) : null;
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
    <meta property="og:type" content="<?= $isArticle ? 'article' : 'website' ?>">
    <meta property="og:site_name" content="<?= esc($site->title()->value()) ?>">
    <meta property="og:url" content="<?= esc($page->url()) ?>">
    <meta property="og:title" content="<?= esc($ogTitle) ?>">
<?php if (!empty($ogDescription)): ?>
    <meta property="og:description" content="<?= esc($ogDescription) ?>">
<?php endif; ?>
<?php if ($ogImage): ?>
    <meta property="og:image" content="<?= $ogImage->url() ?>">
    <meta property="og:image:width" content="<?= $ogImage->width() ?>">
    <meta property="og:image:height" content="<?= $ogImage->height() ?>">
<?php if ($ogImageAlt): ?>
    <meta property="og:image:alt" content="<?= esc($ogImageAlt) ?>">
<?php endif; ?>
<?php endif; ?>
<?php if ($publishedTime): ?>
    <meta property="article:published_time" content="<?= $publishedTime ?>">
<?php endif; ?>
<?php if ($modifiedTime): ?>
    <meta property="article:modified_time" content="<?= $modifiedTime ?>">
<?php endif; ?>
<?php if (kirby()->multilang()): ?>
    <meta property="og:locale" content="<?= MetaHelper::ogLocale(kirby()->language()) ?>">
<?php foreach (kirby()->languages() as $language): ?>
<?php if ($language->code() !== kirby()->language()->code()): ?>
    <meta property="og:locale:alternate" content="<?= MetaHelper::ogLocale($language) ?>">
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= esc($ogTitle) ?>">
<?php if (!empty($ogDescription)): ?>
    <meta name="twitter:description" content="<?= esc($ogDescription) ?>">
<?php endif; ?>
<?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= $ogImage->url() ?>">
<?php if ($ogImageAlt): ?>
    <meta name="twitter:image:alt" content="<?= esc($ogImageAlt) ?>">
<?php endif; ?>
<?php endif; ?>

<?php endif; ?>

<?php if ($enableSchema): ?>
<?php
// Entity Schema (Organization or Person)
$schemaType = $site->schemaType()->isNotEmpty() ? $site->schemaType()->value() : 'Organization';
$schemaName = $site->schemaName()->isNotEmpty() ? $site->schemaName()->value() : $site->title()->value();

$entitySchema = [
    '@context' => 'https://schema.org',
    '@type' => $schemaType,
    'name' => $schemaName,
    'url' => $site->url(),
];

if ($site->ogImage()->isNotEmpty()) {
    $entityImage = $site->ogImage()->toFile();
    if ($entityImage) {
        $entityImageUrl = $entityImage->crop(600, 600)->url();
        // Person uses "image", Organization uses "logo"
        $entitySchema[$schemaType === 'Person' ? 'image' : 'logo'] = $entityImageUrl;
    }
}

if ($schemaType === 'Person' && $site->schemaJobTitle()->isNotEmpty()) {
    $entitySchema['jobTitle'] = $site->schemaJobTitle()->value();
}

if ($site->schemaEmail()->isNotEmpty()) {
    $entitySchema['email'] = $site->schemaEmail()->value();
}

if ($site->metaKitSocialSites()->isNotEmpty()) {
    $socialProfiles = [];
    foreach ($site->metaKitSocialSites()->toStructure() as $social) {
        if ($social->url()->isNotEmpty()) {
            $socialProfiles[] = $social->url()->value();
        }
    }
    if (!empty($socialProfiles)) {
        $entitySchema['sameAs'] = $socialProfiles;
    }
}

// WebSite Schema
$websiteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $site->title()->value(),
    'url' => $site->url(),
    'publisher' => [
        '@type' => $schemaType,
        'name' => $schemaName,
    ],
];

// Add search action only when the site has a published search page
$searchPage = $site->find('search');
if ($searchPage) {
    $websiteSchema['potentialAction'] = [
        '@type' => 'SearchAction',
        'target' => [
            '@type' => 'EntryPoint',
            'urlTemplate' => $searchPage->url() . '?q={search_term_string}'
        ],
        'query-input' => 'required name=search_term_string'
    ];
}

// WebPage Schema
$webPageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $metaTitle,
    'description' => $metaDescription,
    'url' => $page->url(),
    'inLanguage' => MetaHelper::currentLanguageCode(kirby()),
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

// Article schema for article templates
if ($isArticle) {
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => mb_substr($page->title()->value(), 0, 110),
        'description' => $metaDescription,
        'url' => $page->url(),
        'mainEntityOfPage' => $page->url(),
        'inLanguage' => MetaHelper::currentLanguageCode(kirby()),
        'dateModified' => $modifiedTime,
        'publisher' => [
            '@type' => $schemaType,
            'name' => $schemaName,
        ],
    ];
    if ($publishedTime) {
        $articleSchema['datePublished'] = $publishedTime;
    }
    if ($author) {
        $articleSchema['author'] = ['@type' => 'Person', 'name' => $author->value()];
    }
    if ($ogImage) {
        $articleSchema['image'] = $ogImage->url();
    }
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

<?php
// HEX_TAG keeps editor content like "</script>" from closing the script tag
$jsonFlags = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP;
?>
<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
  <?= json_encode($entitySchema, $jsonFlags) ?>
</script>

<script type="application/ld+json">
  <?= json_encode($websiteSchema, $jsonFlags) ?>
</script>

<script type="application/ld+json">
  <?= json_encode($webPageSchema, $jsonFlags) ?>
</script>

<?php if (isset($articleSchema)): ?>
<script type="application/ld+json">
  <?= json_encode($articleSchema, $jsonFlags) ?>
</script>
<?php endif; ?>

<?php if (isset($breadcrumbSchema)): ?>
<script type="application/ld+json">
  <?= json_encode($breadcrumbSchema, $jsonFlags) ?>
</script>
<?php endif; ?>

<?php endif; ?>
