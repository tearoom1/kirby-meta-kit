## [3.1.1](https://github.com/tearoom1/kirby-meta-kit/compare/v3.1.0...v3.1.1) (2026-10-08)


### Bug Fixes

* toolbar groups Display with sort and the search with Filters ([96787bf](https://github.com/tearoom1/kirby-meta-kit/commit/96787bff0b28ef26c981073abdf1161e0e331616))

## [3.1.0](https://github.com/tearoom1/kirby-meta-kit/compare/v3.0.0...v3.1.0) (2026-10-08)


### Features

* Display menu with remembered view, inheritance and page size ([0224a3e](https://github.com/tearoom1/kirby-meta-kit/commit/0224a3e8ab00b13bad813cd5603afb164a40291e))

## [3.0.0](https://github.com/tearoom1/kirby-meta-kit/compare/v2.3.0...v3.0.0) (2026-10-08)


### ⚠ BREAKING CHANGES

* the Panel area is rebuilt. The `variant` prop of the
meta-kit-view component and the old table/stats components are gone;
the "Meta Kit (Neu)" area and its `meta-kit-v2` route no longer exist.
Custom CSS targeting the old `k-meta-kit-table` or `k-meta-kit-stats`
classes will not apply.

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>

### Features

* calmer v2 overview, Kirby status icons, sub-dialogs in review style ([106a07b](https://github.com/tearoom1/kirby-meta-kit/commit/106a07b8fc9428846fc8a45b219982c54f28afed))
* length meter in v2 edit dialogs, wrapping slugs, styled generate dialog ([77f0e5f](https://github.com/tearoom1/kirby-meta-kit/commit/77f0e5f5d817fc414fd1774da7c45f41488ecfa4))
* new panel interface replaces the old table and stats cards ([505ae26](https://github.com/tearoom1/kirby-meta-kit/commit/505ae2696c6df8a3947f16a3e66fb90052e30f75))
* temporary "Meta Kit (Neu)" area for the design comparison ([f465d3f](https://github.com/tearoom1/kirby-meta-kit/commit/f465d3f5eda72caef10f05f2584aa5ad67f7232a))
* v2 inheritance switch, source word beneath inherited values, calmer table ([03e30e3](https://github.com/tearoom1/kirby-meta-kit/commit/03e30e340f509d0c6a7af86b0e8b29c7a00fa5b3))
* v2 OG image column shows a check instead of words and dots ([8a7bf99](https://github.com/tearoom1/kirby-meta-kit/commit/8a7bf997773a71ff5974581ca1b4747eefc6ee26))
* v2 rich tooltips, quieter headers, all dialogs and dark mode in v2 style ([9d339bf](https://github.com/tearoom1/kirby-meta-kit/commit/9d339bf8207fd10a104467792915be415d1a3086))
* v2 toolbar controls wrap as a block ([dc6ca26](https://github.com/tearoom1/kirby-meta-kit/commit/dc6ca2655f077ddfd06e824581f05338b17cc57a))


### Bug Fixes

* closed dialogs reappeared when another dialog opened in Kirby 5 ([6dafb4c](https://github.com/tearoom1/kirby-meta-kit/commit/6dafb4ca865a9d122e6b9557aaa2d356812df747))
* v2 dark mode level colours and meter zones stay readable ([7687560](https://github.com/tearoom1/kirby-meta-kit/commit/7687560c4e62aa5b601177a2c1429b93eabfaf76))

## [2.3.0](https://github.com/tearoom1/kirby-meta-kit/compare/v2.2.1...v2.3.0) (2026-10-08)


### Features

* article tags, image alt texts and site name for social previews ([202d375](https://github.com/tearoom1/kirby-meta-kit/commit/202d375bb6e9112057eee5e637dc805df7a0b03b))
* block AI training crawlers and publish llms.txt ([3b34788](https://github.com/tearoom1/kirby-meta-kit/commit/3b34788c29b53d8e008bfedb619aa0784a1d866d))
* cache the sitemap and list page images ([553fa65](https://github.com/tearoom1/kirby-meta-kit/commit/553fa653ddb4d51f2e792dc8361209ce304efe34))
* detect duplicate meta titles and descriptions ([aafc4a6](https://github.com/tearoom1/kirby-meta-kit/commit/aafc4a65f1f3918e4660fb8c139a782cef295c2f))
* generate in steps with progress, cancel and a review before saving ([850dd68](https://github.com/tearoom1/kirby-meta-kit/commit/850dd682de3645382fa9eda836a23c657eaa5ffa))
* German panel interface ([eded021](https://github.com/tearoom1/kirby-meta-kit/commit/eded0218d58a363c00d1e9febee8fef4298be2eb))
* redirect old URLs after slug changes and page moves ([90d2e7a](https://github.com/tearoom1/kirby-meta-kit/commit/90d2e7afa42a1710ad63f8283e1154edac9b6664))


### Bug Fixes

* apply template validation ranges everywhere and stop cutting texts short ([6516525](https://github.com/tearoom1/kirby-meta-kit/commit/6516525b764b4a8f32ae9d744d8e5e01fb03f000))
* bulk edit and generation use the same pages as the table ([410a41a](https://github.com/tearoom1/kirby-meta-kit/commit/410a41a67a15924d107e5bc5820d4c24b24c294d))
* closed dialogs reappeared when another dialog opened in Kirby 5 ([393ad52](https://github.com/tearoom1/kirby-meta-kit/commit/393ad52e5ec921b35892074c89909fe89f648f25))
* dark mode styles never applied in Kirby 5 ([2bbba31](https://github.com/tearoom1/kirby-meta-kit/commit/2bbba31406cfc30f1d54503fce5bae117a8ea48e))
* don't block page saves with auto-generation ([31d5496](https://github.com/tearoom1/kirby-meta-kit/commit/31d549607d2104d5cbceb49aacf7b3b6ba27f747))
* don't keep superuser rights after initializing settings ([22fec25](https://github.com/tearoom1/kirby-meta-kit/commit/22fec256ff7adeafd85824d10db4c78d8b0cf090))
* edit dialog length colours match the table ([89632b8](https://github.com/tearoom1/kirby-meta-kit/commit/89632b8bd4430065b5d8829b99993dd18f76eaa0))
* escape schema JSON-LD so content can't close the script tag ([d66d75f](https://github.com/tearoom1/kirby-meta-kit/commit/d66d75fe85657557a753bceb0fc44a2491dffe57))
* ISO publish date for articles with the intl date handler ([ef413ce](https://github.com/tearoom1/kirby-meta-kit/commit/ef413ceca7c63886478deb0dc37d335f64fad551))
* only read blueprint fields for AI generation and skip pages without text ([d42c4ed](https://github.com/tearoom1/kirby-meta-kit/commit/d42c4ed4acf2d866b9a5642921bf409ff4eba05b))
* page methods use the panel's content detection ([fb41eb5](https://github.com/tearoom1/kirby-meta-kit/commit/fb41eb54e36018964754e4b917547dac05cda832))
* robots.txt toggles stored as "false" no longer count as enabled ([2a60d56](https://github.com/tearoom1/kirby-meta-kit/commit/2a60d566072aebde75cebce9813407cdaef5c359))
* translate the OG image tooltips in the table ([207fa0d](https://github.com/tearoom1/kirby-meta-kit/commit/207fa0d4ecf730d86e782a47e4d16359e9b2bbd6))

## [2.2.1](https://github.com/tearoom1/kirby-meta-kit/compare/v2.2.0...v2.2.1) (2026-10-08)


### Bug Fixes

* correct grammar in AI settings info text ([cfacaa0](https://github.com/tearoom1/kirby-meta-kit/commit/cfacaa038470de164efcc1254d98ecd0e10cc2d1))

