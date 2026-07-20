# Release process

## Checklist (v1.0.4)

1. [CHANGELOG.md](CHANGELOG.md) — version **1.0.4** and date set; `[Unreleased]` empty.
2. [UPGRADING.md](UPGRADING.md) — upgrade notes for **1.0.4** (no consumer API change).
3. `make release-check` — check-no-cursor-coauthor, cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo release-check.
4. Commit: `Release v1.0.4`
5. Annotated tag: `git tag -a v1.0.4 -m "Release v1.0.4"`
6. `make check-no-cursor-coauthor` again **before** push (REQ-GIT-001).
7. Push: `git push origin main && git push origin v1.0.4`
8. GitHub Actions `release.yml` creates the GitHub Release from the tag and changelog.
9. Confirm Packagist auto-update (or trigger manual sync).

## Standard workflow (next releases)

1. Move `[Unreleased]` entries in [CHANGELOG.md](CHANGELOG.md) to a new `## [x.y.z] - YYYY-MM-DD` section.
2. Update [UPGRADING.md](UPGRADING.md) if there are breaking or notable changes.
3. Update integrator docs ([USAGE.md](USAGE.md), [SECURITY.md](SECURITY.md)) when behavior changes.
4. Run `make release-check`.
5. Commit, tag (`git tag -a vx.y.z -m "Release vx.y.z"`), push branch and tags.
6. Verify GitHub Release and Packagist.

After creating the release commit and tag, run `make check-no-cursor-coauthor` again **before** `git push` (REQ-GIT-001). The release commit itself is not covered by an earlier `release-check` run.
