# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **RedirectToRefererTrait** — `redirectToReferer()` third argument is `int $status` (aligns with `AbstractController::redirectToRoute()` and static analysis).

### Documentation

- **README** — Demo section: default **`APP_ENV=dev`** + **Caddyfile.dev** (no PHP worker), link to `DEMO-FRANKENPHP.md`, ports **8010** / **8011**, `make -C demo up-symfony8` example.
- **docs/DEMO-FRANKENPHP.md** — Dev vs prod (worker), entrypoint/mounts; **`bundles.php`** example aligned with demos.
- **demo/README.md** — Same dev/prod note (Caddyfile.dev).

### Added

- **RedirectToRefererTrait** — `redirectToReferer(Request $request, ?array $params = [], int $status = 302)` for controllers extending AbstractController. Configurable default route via `nowo_controller_kit.default_route`.
- **SafeForwardTrait** — `safeForward(string $controllerClass, string $methodName, ?array $pathParams = [], ?array $queryParams = [])` with method-existence check before forwarding.
- Configuration: `nowo_controller_kit.default_route` (default `homepage`).
- Docs: INSTALLATION, CONFIGURATION, USAGE, CONTRIBUTING, CHANGELOG, UPGRADING, RELEASE, SECURITY.
