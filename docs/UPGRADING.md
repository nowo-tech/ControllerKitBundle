# Upgrading

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
