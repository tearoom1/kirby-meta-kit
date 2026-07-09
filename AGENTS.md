# Release workflow

- Do not create release tags or GitHub releases manually.
- Commit user-facing bug fixes with a Conventional Commit message starting with `fix:`.
- Push the commit to `main`.
- The `Releases` GitHub Actions workflow uses `TriPSs/conventional-changelog-action` to bump the patch version in `composer.json`, create the release commit and tag, and uses `softprops/action-gh-release` to publish the GitHub release.
- Verify that the workflow completed successfully and that the expected tag and GitHub release exist.
