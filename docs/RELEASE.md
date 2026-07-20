# Release process

## Checklist (v2.0.0)

1. [CHANGELOG.md](CHANGELOG.md) — version **2.0.0** and date set; `[Unreleased]` empty.
2. [UPGRADING.md](UPGRADING.md) — upgrade notes for **2.0.0** (PHP 8.2 / Symfony 7 minimum; stay on `^1.0` if still on PHP 8.1 or Symfony 6).
3. [INSTALLATION.md](INSTALLATION.md) / README — requirements aligned with `composer.json`.
4. `make release-check` — check-no-cursor-coauthor, cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo release-check.
5. Commit: `Release v2.0.0`
6. Annotated tag: `git tag -a v2.0.0 -m "Release v2.0.0"`
7. `make check-no-cursor-coauthor` again **before** push (REQ-GIT-001).
8. Push: `git push origin main && git push origin v2.0.0`
9. GitHub Actions `release.yml` creates the GitHub Release from the tag and changelog.
10. Confirm Packagist auto-update (or trigger manual sync).

## Standard workflow (next releases)

1. Move `[Unreleased]` entries in [CHANGELOG.md](CHANGELOG.md) to a new `## [x.y.z] - YYYY-MM-DD` section.
2. Update [UPGRADING.md](UPGRADING.md) if there are breaking or notable changes.
3. Update integrator docs ([USAGE.md](USAGE.md), [SECURITY.md](SECURITY.md)) when behavior changes.
4. Run `make release-check`.
5. Commit, tag (`git tag -a vx.y.z -m "Release vx.y.z"`), push branch and tags.
6. Verify GitHub Release and Packagist.

After creating the release commit and tag, run `make check-no-cursor-coauthor` again **before** `git push` (REQ-GIT-001). The release commit itself is not covered by an earlier `release-check` run.
