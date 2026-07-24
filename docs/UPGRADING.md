# Upgrading

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
