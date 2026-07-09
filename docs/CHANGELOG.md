# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[1.0.1]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.1
[1.0.0]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.0
