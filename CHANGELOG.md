## [4.0.3](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.2...v4.0.3) (2026-11-18)


### Bug Fixes

* use page model methods for robots, share image and author again ([8fd2205](https://github.com/tearoom1/kirby-meta-kit/commit/8fd22057a8be7134c56b1cfe55b7d6a58e62a34b))

## [4.0.2](https://github.com/tearoom1/kirby-meta-kit/compare/v4.0.1...v4.0.2) (2026-11-10)


### Bug Fixes

* stop loading the plugin's own vendor autoloader ([e861240](https://github.com/tearoom1/kirby-meta-kit/commit/e8612406ee6a5d90c0bd1153660c609ce6558154))

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

