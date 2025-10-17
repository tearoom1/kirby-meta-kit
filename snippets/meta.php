<?php
/**
 * Meta tags snippet for SEO
 */
$site = $site ?? site();
$page = $page ?? page();

// Get meta title
$metaTitle = $page->metaTitle()->isNotEmpty() 
    ? $page->metaTitle()->value() 
    : ($page->title()->value() . ' | ' . $site->title()->value());

// Get meta description
if ($page->metaDescription()->isNotEmpty()) {
    $metaDescription = $page->metaDescription()->excerpt(160);
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
$canonical = $page->canonicalUrl()->isNotEmpty() 
    ? $page->canonicalUrl()->value() 
    : $page->url();
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
<?php if ($page->noIndex()->isTrue()): ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif; ?>

<!-- Additional meta tags -->
<?php if ($page->metaKeywords()->isNotEmpty()): ?>
    <meta name="keywords" content="<?= $page->metaKeywords()->html() ?>">
<?php endif; ?>

<?php if ($page->metaRobots()->isNotEmpty()): ?>
    <meta name="robots" content="<?= $page->metaRobots()->html() ?>">
<?php endif; ?>
