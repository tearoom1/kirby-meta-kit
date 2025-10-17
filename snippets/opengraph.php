<?php
/**
 * OpenGraph and social meta tags
 */
$site = $site ?? site();
$page = $page ?? page();

// Get SEO data from object field
$seoData = $page->seo()->toObject();

// Get site settings
$separator = $site->titleSeparator()->or('|')->value();
$appendSiteName = $site->appendSiteName()->or('true')->toBool();

// Get the featured image or fallback to site image
if ($seoData && $seoData->ogImage()->isNotEmpty()) {
    $ogImage = $seoData->ogImage()->toFile();
} else {
    $ogImage = $site->ogImage()->toFile() ?? null;
}

// Get OpenGraph title
if ($seoData && $seoData->ogTitle()->isNotEmpty()) {
    $ogTitle = $seoData->ogTitle()->value();
} else {
    $ogTitle = $page->title()->value();
}

// Append site name if enabled and not already included
if ($appendSiteName && !str_contains($ogTitle, $site->title()->value())) {
    $ogTitle = $ogTitle . ' ' . $separator . ' ' . $site->title()->value();
}

// Get OpenGraph description
if ($seoData && $seoData->ogDescription()->isNotEmpty()) {
    $ogDescription = $seoData->ogDescription()->excerpt(160);
} elseif ($seoData && $seoData->metaDescription()->isNotEmpty()) {
    $ogDescription = $seoData->metaDescription()->excerpt(160);
} elseif ($site->ogDescription()->isNotEmpty()) {
    $ogDescription = $site->ogDescription()->excerpt(160);
} else {
    $ogDescription = $page->text()->excerpt(160);
}
?>

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
