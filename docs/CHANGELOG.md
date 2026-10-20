# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)
- [[2.2.0] - 2026-10-14](#220---2026-10-14)
- [[2.1.0] - 2026-10-13](#210---2026-10-13)
- [[2.0.14] - 2026-10-13](#2014---2026-10-13)
- [[2.0.13] - 2026-10-13](#2013---2026-10-13)
- [[2.0.12] - 2026-10-09](#2012---2026-10-09)
- [[2.0.11] - 2026-09-27](#2011---2026-09-27)
- [[2.0.10] - 2026-09-24](#2010---2026-09-24)
- [[2.0.9] - 2026-08-24](#209---2026-08-24)
- [[2.0.8] - 2026-08-19](#208---2026-08-19)
- [[2.0.7] - 2026-08-18](#207---2026-08-18)
- [[2.0.6] - 2026-07-29](#206---2026-07-29)
- [[2.0.5] - 2026-07-29](#205---2026-07-29)
- [[2.0.4] - 2026-07-28](#204---2026-07-28)
- [[2.0.3] - 2026-07-27](#203---2026-07-27)
- [[2.0.2] - 2026-07-24](#202---2026-07-24)
- [[2.0.1] - 2026-07-22](#201---2026-07-22)
- [[2.0.0] - 2026-07-20](#200---2026-07-20)
- [[1.0.4] - 2026-07-20](#104---2026-07-20)
- [[1.0.3] - 2026-07-20](#103---2026-07-20)
- [[1.0.2] - 2026-07-20](#102---2026-07-20)
- [[1.0.1] - 2026-07-09](#101---2026-07-09)
- [[1.0.0] - 2026-06-11](#100---2026-06-11)

## [Unreleased]

### Changed

- Dependencies refreshed (Composer and pnpm/npm lock files of the bundle and its demos, campaign 2026-10-19).
- `.php-cs-fixer.dist.php`: `new ParallelConfig(2)` (REQ-CS-001).

### Documentation

- Documentation: wave-1 REQ compliance — canonical issue templates, canonical FrankenPHP worker-mode claim in README, README documentation list order (PSR under Additional documentation), CONTRIBUTING Cursor Agent queue settings (REQ-IDE-006), tables of contents on long docs.

## [2.2.0] - 2026-10-14

### Added

- `Attribute\MapScopedEntity` controller argument attribute + `ValueResolver\ScopedEntityValueResolver` (spec 003): loads a Doctrine ORM entity with one query joined on its owner association (another route attribute), so an entity of another owner is the same 404 as a missing one. Options `parent`, `parentId` (default `{parent}Id`), `parentField`, `id`, `field`, `objectManager`. Nullable arguments receive `null`; identifiers malformed for the column type (DBAL `ConversionException`) are a 404; mapping mistakes are `LogicException`s. The attribute is not final, so apps can preset their owner (e.g. `#[MapProjectScoped]`).
- `DependencyInjection\Compiler\ScopedEntityValueResolverPass`: registers the resolver as a targeted value resolver only when `doctrine/orm` is installed and the `doctrine` registry (DoctrineBundle) exists; an application service with the same id is kept.
- `composer.json` `suggest`: `doctrine/orm` and `doctrine/doctrine-bundle`.

### Changed

- Development: `doctrine/orm` (^3.4) and `doctrine/doctrine-bundle` (^2.13 || ^3.0) added to `require-dev` (unit tests on in-memory SQLite + a kernel test with FrameworkBundle + DoctrineBundle). CI resolves `doctrine/*` together with `symfony/*` per matrix cell (DoctrineBundle 3 for Symfony 8). Development lock refreshed (Symfony 7.4.21 patch releases).

[2.2.0]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.2.0

## [2.1.0] - 2026-10-13

### Added

- `Controller\RequiresValidFormTrait` for `AbstractController` subclasses (spec 002):
  - `requireValidForm(FormInterface $form, string $message = 'Invalid form submission.')`: 403 when the form is not submitted or invalid.
  - `requireValidCsrfForm(FormInterface $form)`: same, with the message `Invalid CSRF token.`.
  - `acceptSubmittedForm(FormInterface $form, string $field, string $invalidFieldFlash, string $csrfField = '_token'): bool`: 403 when not submitted, on a CSRF failure or on other errors; when every error comes from `$field`, adds an `error` flash and returns `false`.
- `composer.json` `suggest`: `symfony/form` and `symfony/security-core` (only needed by hosts that use the new trait; the bundle still installs without them).

### Changed

- Development: `symfony/form`, `symfony/security-core` and `symfony/security-csrf` added to `require-dev` for the trait tests.
- `redirectToReferer()`: the temporary `GET` switch of the shared router context (restored in `finally`) is marked `// @igor-ignore`, so the Igor worker audit in `make release-check` passes again (no behaviour change).

[2.1.0]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.1.0

## [2.0.14] - 2026-10-13

### Security

- `redirectToReferer()`: the matched route name is read before the Referer query string is merged, and query keys starting with `_` are ignored, so a Referer such as `/page?_route=other` can no longer redirect to a different route.

### Fixed

- `redirectToReferer()` now matches the Referer path as a `GET` request (restoring the shared router context afterwards), so redirecting after a `POST` to a GET-only page no longer falls back to `default_route`.
- `redirectToReferer()` strips the request base path (app served from a sub-directory) from the Referer path before matching.

[2.0.14]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.14

## [2.0.13] - 2026-10-13

### Changed

- Development: `composer.json` pins `config.platform.php` to 8.2.0 so the committed lock stays installable on the minimum PHP; CI overrides the platform per matrix cell. Dev lock re-resolved for PHP 8.2 (Symfony 8.1 components -> 7.4 LTS).

### Dependencies

- `igor-php/igor-php` 0.9.7 → 0.10.1 (require-dev constraint + development lock; #20).

[2.0.13]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.13

## [2.0.12] - 2026-10-09

### Dependencies

- Runtime lock: Symfony 8.1 components -> v8.1.8 (framework-bundle, dependency-injection, http-kernel, http-foundation, error-handler; Dependabot #19), polyfills -> v1.43.
- Dev tooling: `phpstan/phpstan` 2.3.1, `phpstan/phpstan-symfony` 2.1.0, `rector/rector` 2.7.0, `phpunit/phpunit` 10.5.66, `nowo-tech/phpstan-frankenphp` v1.2.3.
- Demo (Symfony 8): Symfony v8.1.8, `twig/twig` v3.30.0, `twig/extra-bundle` ^3.29, PHPUnit ^13.4.1, `nowo-tech/hot-reload-bundle` ^1.5.4, `nowo-tech/twig-inspector-bundle` v1.1.7.

[2.0.12]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.12

## [2.0.11] - 2026-09-27

### Added

- **REQ-CS-008:** `igor-php/igor-php` (require-dev only), root `igor.json`, Composer/`Makefile` `igor` target, and `release-check` wiring for FrankenPHP worker-state audit.

### Changed

- **Worker safety (Igor):** justified `// @igor-ignore` annotations and/or `ResetInterface` / request-scoped fixes so `make igor` passes on package `src/`.

[2.0.11]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.11

## [2.0.10] - 2026-09-24

### Fixed

- **`RedirectToRefererTrait`** — resolve the router from the controller container (`AbstractController`) instead of relying on a missing `getRouter()` host method (which made valid Referers always fall back to `default_route`).

### Changed

- **FrankenPHP worker** — PHPStan includes `ruleset-worker-strict` + `ruleset-hardening`; traits documented as safe when the kernel is **not** reset between requests.
- **Specs** — `FR-WRK-001` worker / no-kernel-reset requirement in `specs/001-baseline/spec.md`.

### Documentation

- Added [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md); linked from README, DEMO-FRANKENPHP, SECURITY, and USAGE.

### Tests

- Unit coverage for `getRouter()` / `resolveControllerContainer()` (missing router, wrong type, invalid or absent container).

## [2.0.9] - 2026-08-24

### Changed

- **Docs:** PHP-FIG PSR evaluation (REQ-CS-007).

### Notes

- **No API or configuration changes** for integrators unless noted above.

## [2.0.8] - 2026-08-19

### Security

- **CI:** run `composer audit --locked` after dependency install (REQ-SEC / P3).

## [2.0.7] - 2026-08-18

### Changed

- **Demos:** pin `nowo-tech/hot-reload-bundle` to `^1.4` with FrankenPHP Mercure/`hot_reload` (`dev`/`test` only).

[2.0.7]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.7

## [2.0.6] - 2026-07-29

### Fixed

- **REQ-MAKE-010** — Root and `demo/symfony8` Makefiles prefer Compose V2 (`docker compose`) with fallback to `docker-compose` V1, so `make demo-smoke` / `make up` work on GitHub Actions runners that lack the legacy binary. Docker CLI is resolved via `command -v` so a local demo `docker/` directory cannot shadow the binary when `PATH` contains empty segments.

## [2.0.5] - 2026-07-29

### Fixed

- **REQ-MAKE-008 / CI** — Root and demo Makefiles use soft `-include` for monorepo `update-deps` helpers so `make demo-smoke` works on standalone GitHub Actions checkouts (helpers absent outside the local monorepo).

## [2.0.4] - 2026-07-28

### Added

- **CI** — PHPStan job in `.github/workflows/ci.yml` (REQ-CS-006).
- **`.github/workflows/demo-smoke.yml`** — scheduled / tag / dispatch smoke (REQ-TEST-011).

### Changed

- PHPStan: explicit `ignoreErrors: []` (REQ-CS-006).
- Spec Kit inventory maps root `NowoControllerKitBundle.php` (6 production sources).
- `composer.json` keywords: add `php`, `symfony-bundle`, `frankenphp`.
- **REQ-REL-003** — `check-open-prs.sh` resolves `--repo` from `origin` when `gh` cannot infer the GitHub host from SSH remotes.

### Documentation

- Table of contents on long docs (REQ-DOCS-005).
- **REQ-DOCS-018** — GitHub topic `symfony-bundle` present on About topics.

## [2.0.3] - 2026-07-27

### Added

- **REQ-TEST-011** — `make demo-smoke` (root + `demo/`) boots the Symfony 8 demo and asserts HTTP **200** on `:8011`; included in demo `release-check`.
- **REQ-SF-005** — `SYMFONY_DEPRECATIONS_HELPER=max[direct]=0` in `phpunit.xml.dist` and CI; `symfony/phpunit-bridge` in `require-dev`.
- **REQ-REL-003 / REQ-MAKE-002** — `.scripts/check-open-prs.sh` and `make check-open-prs` wired into `release-check`.

### Changed

- **REQ-DEMO-010** — Symfony 8 demo FrankenPHP image bumped to **PHP 8.5** (`dunglas/frankenphp:1-php8.5-alpine`); demo `require.php` is `>=8.5`.
- **REQ-PHP-001** — `NowoControllerKitBundle`, `Configuration`, and `ControllerKitExtension` marked `final`.
- **REQ-DOCS-018** — GitHub About: description, Packagist website, and topics (`php`, `symfony`, `symfony-bundle`, …).
- **REQ-OBS-001 / REQ-SEC-004** — `docs/SECURITY.md`: logging policy, corrected same-host Referer mitigation, AI audit grade **Low**.

## [2.0.2] - 2026-07-24

### Added

- **REQ-CS-005** — `nowo-tech/phpstan-frankenphp` in `require-dev` with `ruleset-classic` + `ruleset-worker` in `phpstan.neon.dist`.
- **REQ-DOCS-017** — FrankenPHP Friendly Worker Mode banner in README (`docs/images/frankenphp-friendly.png`).
- **REQ-MAKE-007** — `make down-dev` (root and demo).

### Changed

- **REQ-DEMO-003 / REQ-ENV-001** — Demo `.gitignore` categories aligned; `install` copies `.env.example` → `.env` when missing.
- **REQ-DOCS-002** — README `## Documentation` link order; CI docs under Additional documentation.
- **Rector** — Target PHP version set to **8.2** (matches package minimum).
- **PHP-CS-Fixer** — `fully_qualified_strict_types.import_symbols` enabled.

## [2.0.1] - 2026-07-22

### Changed

- **Demo FrankenPHP** — Runtime mode via **`FRANKENPHP_MODE`** (`classic` | `worker`, default `worker`) in `.env` / Compose; dedicated `demo/symfony8/docker/entrypoint.sh`.

### Documentation

- **DEMO-FRANKENPHP.md** / README — Document `FRANKENPHP_MODE` (no longer driven only by `APP_ENV`).

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

[2.0.10]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.10
[2.0.9]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.9
[2.0.8]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.8
[2.0.7]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.7
[2.0.6]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.6
[2.0.5]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.5
[2.0.4]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.4
[2.0.3]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.3
[2.0.2]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.2
[2.0.1]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.1
[2.0.0]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v2.0.0
[1.0.4]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.4
[1.0.3]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.3
[1.0.2]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.2
[1.0.1]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.1
[1.0.0]: https://github.com/nowo-tech/ControllerKitBundle/releases/tag/v1.0.0
