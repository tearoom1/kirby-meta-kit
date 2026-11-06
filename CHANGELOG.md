## [4.0.1](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.0...v4.0.1) (2026-11-06)


### Bug Fixes

* translate Panel labels with AI enabled and in every Panel language ([904e597](https://github.com/tearoom1/kirby-meta-kit/commit/904e59793acd96e7e445322abe72f811606c1e9e)), closes [#4](https://github.com/tearoom1/kirby-meta-kit/issues/4)

## [4.0.0](https://github.com/tearoom1/kirby-meta-kit/compare/v3.7.1...v4.0.0) (2026-11-03)


### ⚠ BREAKING CHANGES

* Sites without a stored "Append site name to" setting no
longer get the site name on og:title and twitter:title. Sites that saved
the setting keep it; uncheck "OG Title" in the site settings to drop it.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
* On multilingual sites the site name appending, title
separator, social profiles and the AI, sitemap and robots.txt settings
of the default language now apply to every language. Different values
in other languages are ignored and removed when the site is saved next.
To keep one of them per language, redefine it in the site blueprint
with `translate: true`.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>

### Features

* maintain site settings once for all languages ([5dbd844](https://github.com/tearoom1/kirby-meta-kit/commit/5dbd84464ccc1e8c3d2e24a55b546e0b13bba9c8))
* OG description ranges from the platforms, site name on meta titles only ([0349707](https://github.com/tearoom1/kirby-meta-kit/commit/0349707bf9a50c296c3ced4834639f96a716bc93))

## [3.7.1](https://github.com/tearoom1/kirby-meta-kit/compare/v3.7.0...v3.7.1) (2026-11-03)


### Bug Fixes

* cache site settings per language ([c7a2c77](https://github.com/tearoom1/kirby-meta-kit/commit/c7a2c77abe91b0271f8765025fd5ce8b5aaa90bf))

## [3.7.0](https://github.com/tearoom1/kirby-meta-kit/compare/v3.6.0...v3.7.0) (2026-11-03)


### Features

* add SoundCloud, Spotify, Bandcamp, Vimeo, Mastodon and Bluesky profiles ([c5467dd](https://github.com/tearoom1/kirby-meta-kit/commit/c5467dd9498daf391946b9a9d6490c9b648d7fe0))


### Bug Fixes

* always list the home page in the sitemap ([680c40a](https://github.com/tearoom1/kirby-meta-kit/commit/680c40a38bfdd1a71465affb50d010ad3f5a9a72))
* escape titles and descriptions exactly once ([553b243](https://github.com/tearoom1/kirby-meta-kit/commit/553b2432fb5c1573b4be31d6e5cd7593049911cb))

## [3.6.0](https://github.com/tearoom1/kirby-meta-kit/compare/v3.5.0...v3.6.0) (2026-11-02)


### Features

* let pages inherit fields from their parents ([895d9f9](https://github.com/tearoom1/kirby-meta-kit/commit/895d9f9be22b5d85d77cadf400f9f28792003b2e))

