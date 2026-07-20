# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-07-20

### Changed

- **Minimum requirements** — PHP `>=8.2` (<8.6); Symfony `^7.0 || ^8.0` (Symfony 6.x and PHP 8.1 no longer supported).
- **CI matrix** — Dropped Symfony **6.4**; tests Symfony **7.0**, **7.4**, **8.0**, and **8.1** on PHP 8.2–8.5 (Symfony 8.x still only on PHP 8.4+).

### Removed

- **`demo/symfony7`** — FrankenPHP demo for Symfony 7.4 (port **8010**). Use `demo/symfony8` (Symfony **8.1**, port **8011**).

## [1.0.4] - 2026-07-20

### Fixed

- **CI** — `code-style` / `code-style-check` jobs use PHP **8.4** so `composer install` works with a Symfony **8.1** lockfile (`php >=8.4.1`).

## [1.0.3] - 2026-07-20

### Fixed

- **CI** — Removed `composer config platform.php 8.4` for Symfony 8 jobs. Composer treated `8.4` as `8.4.0`, which failed Symfony **8.1** (`php >=8.4.1`). Matrix already runs Symfony 8 only on PHP 8.4+.

### Changed

- **Dev dependencies** — `friendsofphp/php-cs-fixer` **3.95.15**.
- **GitHub Actions** — `actions/checkout` **v7**, `actions/github-script` **v9** (CodeRabbit workflow).

## [1.0.2] - 2026-07-20

### Added

- **REQ-GIT-001** — Git hygiene: `.githooks/commit-msg`, `make setup-hooks`, `make check-no-cursor-coauthor`, CI job `git-hygiene`, and [`docs/GITHUB_CI.md`](GITHUB_CI.md).
- **Code of Conduct** — [Contributor Covenant](../CODE_OF_CONDUCT.md).
- **Tests** — `RedirectToRefererTrait`: same-host Referer with unmatched path falls back to `default_route`.

### Documentation

- **CONTRIBUTING.md** — Code of Conduct and git hooks setup.
- **RELEASE.md** — Re-run co-author check before push after the release commit.
- **README** — Links to GITHUB_CI.md and CODE_OF_CONDUCT.md.

## [1.0.1] - 2026-07-09

### Security

- **RedirectToRefererTrait** — Referer URLs whose host differs from the current request host no longer redirect externally; they fall back to `nowo_controller_kit.default_route` (mitigates open redirect via forged Referer).

### Added

- **GitHub Spec Kit** — Baseline spec (`specs/001-baseline/`), `.specify/` templates, Cursor Agent skills, and [`SPEC-KIT.md`](SPEC-KIT.md).
- **CodeRabbit** — `.coderabbit.yaml` and CI workflow for automated PR review.

### Changed

- **RedirectToRefererTrait** — Referer validation now requires the same host as the current request (case-insensitive), in addition to a resolvable route path.
- **composer.json** — Corrected GitHub repository URLs in `homepage` and `support`.

### Documentation

- **SPEC-DRIVEN-DEVELOPMENT.md** — Spec Kit layer and maintainer workflow.
- **README** — Link to SPEC-KIT.md.
- **USAGE.md** — Document same-host Referer validation.

## [1.0.0] - 2026-06-11

First stable release.

### Added

- **RedirectToRefererTrait** — `redirectToReferer(Request $request, ?array $params = [], int $status = 302)` for controllers extending `AbstractController`. Configurable default route via `nowo_controller_kit.default_route`.
- **SafeForwardTrait** — `safeForward(string $controllerClass, string $methodName, ?array $pathParams = [], ?array $queryParams = [])` with method-existence check before forwarding.
- Configuration: `nowo_controller_kit.default_route` (default `homepage`).
- Symfony Flex recipe (`.symfony/recipe/nowo-tech/controller-kit-bundle/1.0`).
- FrankenPHP demos: `demo/symfony7` (Symfony **7.4**) and `demo/symfony8` (Symfony **8.1**, PHP **8.4+**).
- Docs: INSTALLATION, CONFIGURATION, USAGE, CONTRIBUTING, CHANGELOG, UPGRADING, RELEASE, SECURITY, ENGRAM, DEMO-FRANKENPHP, SPEC-DRIVEN-DEVELOPMENT.

### Changed

- **Symfony compatibility (REQ-SF-001 / REQ-SF-002)** — CI matrix tests Symfony **7.4**, **8.0**, and **8.1**; `composer.json` constraints `^6.0 || ^7.0 || ^8.0` for broader installs.
- **RedirectToRefererTrait** — `redirectToReferer()` third argument is `int $status` (aligns with `AbstractController::redirectToRoute()` and static analysis).

### Documentation

- **README** — Requirements, badges, demo ports **8010** / **8011**, FrankenPHP dev vs worker mode.
- **docs/DEMO-FRANKENPHP.md** — Dev vs prod (worker), entrypoint/mounts; `bundles.php` example with Debug + Web Profiler + Twig Inspector.
- **demo/README.md** — Symfony versions and dev/prod Caddyfile note.
- **Demo Makefiles** — removed broken `REQ-MAKE-008` includes; `release-check` runs `test-all` (FrankenPHP images have no coverage driver).

[2.0.0]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.0
[1.0.4]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.4
[1.0.3]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.3
[1.0.2]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.2
[1.0.1]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.1
[1.0.0]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.0
