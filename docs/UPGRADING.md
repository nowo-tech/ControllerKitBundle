# Upgrading

## Table of contents

- [To 2.0.8](#to-208)
- [To 2.0.7](#to-207)
- [To 2.0.6](#to-206)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 2.0.5](#to-205)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 2.0.4](#to-204)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 2.0.3](#to-203)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 2.0.2](#to-202)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 2.0.1](#to-201)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 2.0.0](#to-200)
  - [Breaking changes](#breaking-changes)
  - [Notable behavior change](#notable-behavior-change)
  - [Removed](#removed)
- [To 1.0.4](#to-104)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 1.0.3](#to-103)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 1.0.2](#to-102)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 1.0.1](#to-101)
  - [Notable behavior change](#notable-behavior-change)
  - [Breaking changes](#breaking-changes)
- [To 1.0.0 (initial release)](#to-100-initial-release)
  - [Requirements](#requirements)
  - [Enable and configure](#enable-and-configure)
  - [Breaking changes](#breaking-changes)

## To 2.0.8

No application upgrade steps.

```bash
composer update nowo-tech/controller-kit-bundle
```

## To 2.0.7

No application upgrade steps. **Demos only:** Hot Reload Bundle `^1.4` (FrankenPHP Mercure/`hot_reload`, `dev`/`test`).

```bash
composer update nowo-tech/controller-kit-bundle
php bin/console cache:clear
```

## To 2.0.6

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^2.0.6
```

### Notable behavior change

None for application code. Maintainer/CI only: Makefiles detect Compose V2 (`docker compose`) first, with fallback to `docker-compose` V1 (REQ-MAKE-010), and resolve the `docker` binary via `command -v` to avoid a local `docker/` directory shadowing the CLI. Runtime API and configuration are unchanged from **2.0.5**.

### Breaking changes

None.

---

## To 2.0.5

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^2.0.5
```

### Notable behavior change

None for application code. Maintainer/CI only: Makefile soft-includes for optional monorepo `update-deps` helpers so GitHub Actions `demo-smoke` does not fail when those files are missing. Runtime API and configuration are unchanged from **2.0.4**.

### Breaking changes

None.

---

## To 2.0.4

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^2.0.4
```

### Notable behavior change

None for application code. This release adds CI PHPStan and scheduled/tag `demo-smoke` workflows, Packagist/GitHub keyword hygiene, docs TOCs, and Spec Kit inventory accuracy. Runtime API and configuration are unchanged from **2.0.3**.

### Breaking changes

None.

---

## To 2.0.3

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^2.0.3
```

### Notable behavior change

None for application code that uses the traits and `nowo_controller_kit.default_route` as documented. Runtime redirect/forward behaviour is unchanged from **2.0.2**.

Demo / maintainer-only: FrankenPHP Symfony 8 demo now targets **PHP 8.5**; `make demo-smoke` / `make check-open-prs` support release hygiene.

### Breaking changes

- **`final` classes** — `NowoControllerKitBundle`, `Nowo\ControllerKitBundle\DependencyInjection\Configuration`, and `ControllerKitExtension` are now `final`. Do not subclass them; configure the bundle via YAML/PHP config instead. Trait APIs (`RedirectToRefererTrait`, `SafeForwardTrait`) are unchanged.

---

## To 2.0.2

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^2.0.2
```

### Notable behavior change

None for application code. This release adds maintainer QA (PHPStan FrankenPHP rules), README worker-mode banner, `make down-dev`, and demo/docs hygiene for bundle standards. Runtime API and configuration are unchanged from **2.0.1**.

`nowo-tech/phpstan-frankenphp` is **`require-dev` only** and is not pulled when applications require this bundle.

### Breaking changes

None.

---

## To 2.0.1

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^2.0.1
```

### Notable behavior change

None for application code. This release only adjusts the FrankenPHP **demo** (`FRANKENPHP_MODE`). Runtime API and configuration are unchanged from **2.0.0**.

### Breaking changes

None.

---

## To 2.0.0

```bash
composer require nowo-tech/controller-kit-bundle:^2.0
```

### Breaking changes

- **PHP** — Minimum is now **8.2** (`>=8.2 <8.6`). PHP **8.1** is no longer supported.
- **Symfony** — Constraints are `^7.0 || ^8.0`. Symfony **6.x** is no longer supported.

Upgrade PHP and Symfony in your application first, then update the bundle. Applications still on PHP 8.1 or Symfony 6 should stay on `^1.0`.

### Notable behavior change

None for application code beyond the platform floor. Runtime API and configuration are unchanged from **1.0.x**. Mandatory tested minors remain Symfony **7.4**, **8.0**, and **8.1** (Symfony 8.x requires PHP **8.4+**). Symfony 7.0–7.3 still resolve when your app allows them.

### Removed

- **`demo/symfony7`** — use `demo/symfony8` only.

---

## To 1.0.4

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^1.0.4
```

### Notable behavior change

None for application code. This release only adjusts CI code-style jobs to PHP **8.4**. Runtime API and configuration are unchanged from **1.0.3**.

### Breaking changes

None.

---

## To 1.0.3

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^1.0.3
```

### Notable behavior change

None for application code. This release fixes Symfony **8.1** CI installs and bumps maintainer/dev tooling only. Runtime API and configuration are unchanged from **1.0.2**.

### Breaking changes

None.

---

## To 1.0.2

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^1.0.2
```

### Notable behavior change

None for application code. This release adds maintainer tooling (git hygiene / CI), Code of Conduct, docs, and an extra unit test. Runtime API and configuration are unchanged from **1.0.1**.

### Breaking changes

None.

---

## To 1.0.1

```bash
composer update nowo-tech/controller-kit-bundle
```

Or require explicitly:

```bash
composer require nowo-tech/controller-kit-bundle:^1.0.1
```

### Notable behavior change

- **`redirectToReferer()`** — A Referer header is accepted only when its **host matches the current request host** (case-insensitive) and its path resolves to a known route. Cross-host Referer values now redirect to `nowo_controller_kit.default_route` instead of an external URL. This is a security hardening; no configuration or signature changes are required.

### Breaking changes

None.

---

## To 1.0.0 (initial release)

This is the first stable release. Install or require the package:

```bash
composer require nowo-tech/controller-kit-bundle:^1.0
```

### Requirements

- PHP `>=8.1` (<8.6). Symfony **8.0** and **8.1** require **PHP 8.4+**.
- Symfony **7.4**, **8.0**, or **8.1** (minimum tested minors). The bundle also resolves on Symfony 6.x and 7.0–7.3 when `composer.json` constraints allow.
- `symfony/framework-bundle` in your application when using the traits with `AbstractController`.

### Enable and configure

1. Register the bundle (or use the Flex recipe).
2. Set `nowo_controller_kit.default_route` in `config/packages/nowo_controller_kit.yaml` to a route that exists in your app.

See [Installation](INSTALLATION.md) and [Configuration](CONFIGURATION.md).

### Breaking changes

None — there is no prior stable release.

For upgrade instructions between other versions, see the [Changelog](CHANGELOG.md).
