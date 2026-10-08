## [3.0.0](https://github.com/tearoom1/kirby-meta-kit/compare/v2.3.0...v3.0.0) (2026-10-08)


### ⚠ BREAKING CHANGES

* The Panel area is rebuilt. The old table and stats components and the `variant` prop of the `meta-kit-view` component are gone. Custom CSS targeting the old `k-meta-kit-table` or `k-meta-kit-stats` classes will not apply.

### Features

* New panel interface ([505ae26](https://github.com/tearoom1/kirby-meta-kit/commit/505ae2696c6df8a3947f16a3e66fb90052e30f75)):
  * overview tiles that show what is open per area and filter the table on click
  * a calmer table: only problems get a dot, inherited values are dimmed, slugs show their parent path dimmed
  * an "Inherited" switch: hide inherited values, dim them, or dim them with the source written beneath (Title, Meta, Site, main language)
  * rich tooltips with the full text, a length meter against the optimal range, and the source of inherited values
  * Edit and Generate act on the selection or on all filtered pages, and the button says which
  * dialogs in the same style with a length meter under every field
  * dark mode throughout

### Bug Fixes

* dark mode level colours and meter zones stay readable ([7687560](https://github.com/tearoom1/kirby-meta-kit/commit/7687560c4e62aa5b601177a2c1429b93eabfaf76))

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

## [2.2.0](https://github.com/tearoom1/kirby-meta-kit/compare/v2.1.6...v2.2.0) (2026-10-08)


### Features

* support Mistral and other OpenAI-compatible AI providers ([2b209cb](https://github.com/tearoom1/kirby-meta-kit/commit/2b209cb1d91e40d76c0dd6df4c5b264e065309fc))


### Bug Fixes

* let AI settings from the panel take effect ([8cd5309](https://github.com/tearoom1/kirby-meta-kit/commit/8cd5309fb4fdb2902a7087a312c3e9f396779057))
* send 'site' as pageId from site-level title and description fields ([5151ac5](https://github.com/tearoom1/kirby-meta-kit/commit/5151ac5b2ff1c402dfcf0fa66b2571a495b7d8a1))

## [2.1.6](https://github.com/tearoom1/kirby-meta-kit/compare/v2.1.5...v2.1.6) (2026-10-08)


### Bug Fixes

* support reasoning models and custom OpenRouter model IDs ([f502cd6](https://github.com/tearoom1/kirby-meta-kit/commit/f502cd674da01f47bb6caf821afa3f5256db25d9))

