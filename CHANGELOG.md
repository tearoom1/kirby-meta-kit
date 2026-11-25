## [5.0.0](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.4...v5.0.0) (2026-11-25)


### ⚠ BREAKING CHANGES

* the Panel bundle needs Kirby 6 (Vue 3).
* Meta Kit 5 requires Kirby 6 and PHP 8.3 or newer. Sites
on Kirby 5 stay on Meta Kit 4. Own blueprints should place the preview
under `fields` instead of `sections` (type `seo-preview`, unchanged).

### Features

* port the Panel components to Vue 3 and Kirby 6 ([2ea873f](https://github.com/tearoom1/kirby-meta-kit/commit/2ea873f170f476718b28e1e5bce1a3a4e2fca7fa))
* register the fields as Kirby 6 field classes ([b5e1021](https://github.com/tearoom1/kirby-meta-kit/commit/b5e1021d80a02f6fedd6b9ce5b78a1e0c12d71a6))


### Bug Fixes

* apply validation rules to page templates named site ([93201e6](https://github.com/tearoom1/kirby-meta-kit/commit/93201e6a0091d44e500382cc0096795fa533c90e))
* correct a typo in the language hint of the site settings ([3838bcb](https://github.com/tearoom1/kirby-meta-kit/commit/3838bcb8eeacec3c38caea7718a17ed111ff1e10))
* give old seo-preview sections a field name of their own ([70ec3c7](https://github.com/tearoom1/kirby-meta-kit/commit/70ec3c7ceb811ef5b2dc88d4715de287457a8ee0))
* keep the card image in the SEO preview after a language switch ([eac304a](https://github.com/tearoom1/kirby-meta-kit/commit/eac304a2e07847e78ab4455f5ee8fc2ab9a898d7))
* keep the live SEO preview on view refreshes and language switches ([9c527c9](https://github.com/tearoom1/kirby-meta-kit/commit/9c527c91181596c7b9b1efb60ef9822f1a97a49f))
* keep the table up to date when an edit dialog closes while saving ([25a6318](https://github.com/tearoom1/kirby-meta-kit/commit/25a6318995135a59fa577d4125f9a627e270219f))
* move retired AI model IDs to "Other model" so the site can be published ([1aa79d8](https://github.com/tearoom1/kirby-meta-kit/commit/1aa79d8242c854c1bacbba651f2a879b7fd6abf6))
* use Kirby 6 color and size tokens ([ace0b84](https://github.com/tearoom1/kirby-meta-kit/commit/ace0b84685c36ccb8c96c7aff7977e2b854337ec))

## [4.0.4](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.3...v4.0.4) (2026-11-22)


### Bug Fixes

* accept flat and nested notation for every option ([392afbc](https://github.com/tearoom1/kirby-meta-kit/commit/392afbc778089613b7a11132180e21e7e7d01bd9))

## [4.0.3](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.2...v4.0.3) (2026-11-18)


### Bug Fixes

* use page model methods for robots, share image and author again ([8fd2205](https://github.com/tearoom1/kirby-meta-kit/commit/8fd22057a8be7134c56b1cfe55b7d6a58e62a34b))

## [4.0.2](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.1...v4.0.2) (2026-11-10)


### Bug Fixes

* stop loading the plugin's own vendor autoloader ([e861240](https://github.com/tearoom1/kirby-meta-kit/commit/e8612406ee6a5d90c0bd1153660c609ce6558154))

## [4.0.1](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.0...v4.0.1) (2026-11-06)


### Bug Fixes

* translate Panel labels with AI enabled and in every Panel language ([904e597](https://github.com/tearoom1/kirby-meta-kit/commit/904e59793acd96e7e445322abe72f811606c1e9e)), closes [#4](https://github.com/tearoom1/kirby-meta-kit/issues/4)

