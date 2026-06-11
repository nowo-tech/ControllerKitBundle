# Release process

## Checklist (v1.0.0)

1. [CHANGELOG.md](CHANGELOG.md) — version **1.0.0** and date set; `[Unreleased]` empty.
2. [UPGRADING.md](UPGRADING.md) — upgrade notes for the new version.
3. `make release-check` — cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo release-check.
4. Commit: `Release v1.0.0`
5. Annotated tag: `git tag -a v1.0.0 -m "Release v1.0.0"`
6. Push: `git push origin main && git push origin v1.0.0`
7. GitHub Actions `release.yml` creates the GitHub Release from the tag and changelog.
8. Confirm Packagist auto-update (or trigger manual sync).

## Standard workflow (next releases)

1. Move `[Unreleased]` entries in [CHANGELOG.md](CHANGELOG.md) to a new `## [x.y.z] - YYYY-MM-DD` section.
2. Update [UPGRADING.md](UPGRADING.md) if there are breaking or notable changes.
3. Run `make release-check`.
4. Commit, tag (`git tag -a vx.y.z -m "Release vx.y.z"`), push branch and tags.
5. Verify GitHub Release and Packagist.
