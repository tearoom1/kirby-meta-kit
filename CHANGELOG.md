## [1.3.0](https://github.com/tearoom1/kirby-meta-kit/compare/v1.2.0...v1.3.0) (2026-03-30)


### Features

* Flat SEO fields — meta fields now live directly on the page content file for better compatibility with Kirby's language system and Git-based workflows
* Language inheritance — table shows when a field is inherited from another language, with a warning indicator and the ability to generate missing translations directly
* Segmented stats bars — overview cards now break down counts into custom values, site-level fallbacks, and missing fields so you can see at a glance where action is needed
* Table filters — filter pages by SEO status, template, and field type
* Table sorting — sort by any column
* Sitemap changefreq and priority settings — configurable per template and per slug
* Edit dialog saves update the table in place without a full page reload
* UI scales down to 1366px wide screens
* Improved tooltips throughout the panel view


### Bug Fixes

* AI-generated values are now clamped to the configured character limits before saving
* Fixed language fallback detection for site-level OG image and description
* Fixed title prompt producing results outside the target length range

## [1.2.0](https://github.com/tearoom1/kirby-meta-kit/compare/v1.1.1...v1.2.0) (2025-12-17)


### Features

* release with new validation rules ([f41c3ac](https://github.com/tearoom1/kirby-meta-kit/commit/f41c3ac5700eb74051742bf3cc57c0eb9bf0fc2f))

## [1.1.1](https://github.com/tearoom1/kirby-meta-kit/compare/v1.1.0...v1.1.1) (2025-12-13)


### Bug Fixes

* do not update og from meta automatically ([61be79d](https://github.com/tearoom1/kirby-meta-kit/commit/61be79def712e9fc37f6fb3f838d02a05286977f))

## [1.1.0](https://github.com/tearoom1/kirby-meta-kit/compare/d3142fee73b6f8a015234a45411d4d06a1c63411...v1.1.0) (2025-11-24)


### Features

* added AI tone ([d3142fe](https://github.com/tearoom1/kirby-meta-kit/commit/d3142fee73b6f8a015234a45411d4d06a1c63411))

