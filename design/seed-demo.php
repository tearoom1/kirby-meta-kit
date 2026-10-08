<?php

/**
 * Demo journal entries that show every Meta Kit inheritance and validation
 * case. Run inside ddev:
 *   ddev exec php site/plugins/meta-kit/design/seed-demo.php
 * Pages that already exist are skipped, so it can run more than once.
 */

require __DIR__ . '/../../../../kirby/bootstrap.php';

$kirby = new Kirby\Cms\App();
$kirby->impersonate('kirby');

$journal = $kirby->page('journal');
$image = $kirby->root('content') . '/1_home/homepage2.jpg';

$text = fn (string $paragraph) => json_encode([[
    'content' => ['text' => '<p>' . $paragraph . '</p>'],
    'id' => Kirby\Toolkit\Str::uuid(),
    'isHidden' => false,
    'type' => 'text',
]]);

$entries = [
    [
        'slug' => 'darjeeling-first-flush',
        'title' => 'Darjeeling first flush',
        'date' => '2026-10-07',
        'body' => 'The first spring harvest from Darjeeling is light, floral and slightly astringent. We brew it cooler than black tea, at about 85 °C, for two and a half minutes.',
        'meta' => [
            'metaTitle' => 'Darjeeling first flush: brewing the spring harvest',
            'metaDescription' => 'How we brew the first spring harvest from Darjeeling: cooler water around 85 °C, two and a half minutes, and why the cup tastes floral rather than malty.',
            'ogTitle' => 'Brewing Darjeeling first flush',
            'ogDescription' => 'Light, floral and a little astringent: the first spring harvest from Darjeeling needs cooler water and a short steep. Here is how we make it in the tea room.',
        ],
        'ogImage' => true,
        'note' => 'All fields of its own, including an OG image',
    ],
    [
        'slug' => 'gyokuro-shaded',
        'title' => 'Gyokuro, shaded',
        'date' => '2026-10-05',
        'body' => 'Gyokuro grows in the shade for three weeks before picking. That shade turns the leaf sweet and brothy, so we brew it at only 60 °C in a small pot.',
        'meta' => [
            'metaTitle' => 'Gyokuro: why shade-grown tea tastes sweet',
            'metaDescription' => 'Gyokuro grows under shade for three weeks, which makes the leaf sweet and brothy. We brew it at 60 °C in a small pot and pour it in several short infusions.',
        ],
        'note' => 'Meta title and description only; OG fields inherit them',
    ],
    [
        'slug' => 'oolong-notes',
        'title' => 'Oolong notes',
        'date' => '2026-10-03',
        'body' => 'Oolong sits between green and black tea. The leaf is partly oxidised, so the same tea can taste of orchids at the first infusion and of honey at the fifth.',
        'meta' => [],
        'note' => 'Nothing set: title from the page title, description from the site',
    ],
    [
        'slug' => 'white-tea-gently',
        'title' => 'White tea, gently',
        'date' => '2026-10-01',
        'body' => 'White tea is the least processed tea we serve: picked, withered and dried. It forgives long steeps, but it rewards patience and soft water.',
        'meta' => [
            'metaTitle' => 'White tea, gently: picking, withering, drying and the long patient steep that it rewards',
            'metaDescription' => 'Soft, patient white tea.',
        ],
        'note' => 'Meta title too long, description too short',
    ],
    [
        'slug' => 'genmaicha-at-lunch',
        'title' => 'Genmaicha at lunch',
        'date' => '2026-09-28',
        'body' => 'Genmaicha mixes green tea with roasted rice. It tastes nutty and warm, which makes it our favourite tea to serve with lunch on cold days.',
        'meta' => [
            'metaTitle' => 'Genmaicha: green tea with roasted rice',
            'metaDescription' => 'Genmaicha mixes green tea with roasted brown rice for a nutty, warming cup. It is the tea we pour most often with lunch when the days get colder outside.',
        ],
        'note' => 'Same meta title and description as "Genmaicha, again"',
    ],
    [
        'slug' => 'genmaicha-again',
        'title' => 'Genmaicha, again',
        'date' => '2026-09-26',
        'body' => 'A second look at genmaicha: this time with matcha dusted over the leaves, which turns the cup bright green and a little creamier than usual.',
        'meta' => [
            'metaTitle' => 'Genmaicha: green tea with roasted rice',
            'metaDescription' => 'Genmaicha mixes green tea with roasted brown rice for a nutty, warming cup. It is the tea we pour most often with lunch when the days get colder outside.',
        ],
        'note' => 'Duplicate of "Genmaicha at lunch"',
    ],
    [
        'slug' => 'pu-erh-storage',
        'title' => 'Pu-erh storage',
        'date' => '2026-09-24',
        'body' => 'Pu-erh keeps changing after it leaves the factory. We store our cakes in paper, away from light and strong smells, at a steady room temperature.',
        'meta' => [
            'metaDescription' => 'How we store pu-erh cakes so they keep ageing well: wrapped in paper, away from light and strong smells, at a steady room temperature all year.',
            'robots' => 'noindex, follow',
        ],
        'note' => 'noindex',
    ],
    [
        'slug' => 'kukicha-twigs',
        'title' => 'Kukicha twigs',
        'date' => '2026-09-22',
        'body' => 'Kukicha is made from the stems and twigs of the tea plant. It is low in caffeine and tastes sweet and grassy, a good cup for late afternoons.',
        'meta' => [
            'metaTitle' => 'Kukicha: the tea made from twigs',
            'metaDescription' => 'Kukicha is made from the stems and twigs of the tea plant. It is low in caffeine and tastes sweet and grassy, which makes it a good late afternoon cup.',
        ],
        'de' => [
            'title' => 'Kukicha-Zweige',
            'metaTitle' => 'Kukicha: der Tee aus Zweigen',
            'metaDescription' => 'Kukicha entsteht aus Stielen und Zweigen der Teepflanze. Er hat wenig Koffein und schmeckt süß und grasig, ideal für den späten Nachmittag im Teeraum.',
        ],
        'note' => 'German metadata of its own',
    ],
    [
        'slug' => 'bancha-basics',
        'title' => 'Bancha basics',
        'date' => '2026-09-20',
        'body' => 'Bancha is the everyday green tea of Japan, picked later in the season. It is mild, a little toasty and very hard to brew badly.',
        'meta' => [
            'metaTitle' => 'Bancha: Japan\'s everyday green tea',
            'metaDescription' => 'Bancha is the everyday green tea of Japan, picked later in the season. It is mild and a little toasty, and it is very hard to brew badly at home.',
        ],
        'de' => [
            'title' => 'Bancha-Grundlagen',
        ],
        'note' => 'German title only: metadata inherited from English',
    ],
    [
        'slug' => 'lapsang-smoke',
        'title' => 'Lapsang smoke',
        'date' => '2026-09-18',
        'body' => 'Lapsang souchong is dried over pine fires, which gives it a smell somewhere between a campfire and smoked ham. People either love it or leave it.',
        'meta' => [
            'metaTitle' => 'Lapsang souchong: the smoked black tea',
        ],
        'status' => 'draft',
        'note' => 'Draft',
    ],
    [
        'slug' => 'caring-for-a-matcha-whisk',
        'title' => 'Caring for a matcha whisk',
        'date' => '2026-09-16',
        'body' => 'A bamboo whisk lasts longer when it dries upright on a holder. Rinse it in warm water only, never with soap, and let the tines keep their shape.',
        'meta' => [
            'metaDescription' => 'A bamboo matcha whisk lasts much longer when it dries upright on a holder. Rinse it with warm water only, never with soap, so the tines keep their shape.',
        ],
        'status' => 'unlisted',
        'note' => 'Unlisted',
    ],
];

foreach ($entries as $entry) {
    if ($journal->findPageOrDraft($entry['slug'])) {
        echo "skip   {$entry['slug']}\n";
        continue;
    }

    $page = $journal->createChild([
        'slug' => $entry['slug'],
        'template' => 'article',
        'content' => [
            'title' => $entry['title'],
            'date' => $entry['date'],
            'author' => 'Meta Kit demo',
            'category' => 'notes',
            'text' => $text($entry['body']),
            ...$entry['meta'],
        ],
    ]);

    if (!empty($entry['ogImage'])) {
        $file = $page->createFile([
            'source' => $image,
            'filename' => 'cover.jpg',
            'content' => ['alt' => 'Tea cups on a wooden tray'],
        ]);
        $page = $page->update(['ogImage' => [$file->uuid()->toString()]]);
    }

    if (!empty($entry['de'])) {
        $page = $page->update($entry['de'], 'de');
    }

    $status = $entry['status'] ?? 'listed';
    if ($status !== 'draft') {
        $page = $page->changeStatus($status);
    }

    echo "create {$entry['slug']} ({$status}) — {$entry['note']}\n";
}
