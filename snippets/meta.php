<?php
/**
 * Meta tags snippet for SEO
 */
$site = $site ?? site();
$page = $page ?? page();

// Get SEO data from object field
$seoData = $page->seo()->toObject();

// Get meta title
if ($seoData && $seoData->metaTitle()->isNotEmpty()) {
    $metaTitle = $seoData->metaTitle()->value();
} else {
    $metaTitle = $page->title()->value() . ' | ' . $site->title()->value();
}

// Get meta description
if ($seoData && $seoData->metaDescription()->isNotEmpty()) {
    $metaDescription = $seoData->metaDescription()->excerpt(160);
} else {
    // Try to generate with AI
    $generated = $page->generateSeoDescription();
    if ($generated) {
        $metaDescription = $generated;
    } elseif ($site->metaDescription()->isNotEmpty()) {
        $metaDescription = $site->metaDescription()->excerpt(160);
    } else {
        $metaDescription = $page->text()->excerpt(160);
    }
}

// Get canonical URL
if ($seoData && $seoData->canonicalUrl()->isNotEmpty()) {
    $canonical = $seoData->canonicalUrl()->value();
} else {
    $canonical = $page->url();
}

// Check noIndex
$noIndex = $seoData && $seoData->noIndex()->isTrue();
?>

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
