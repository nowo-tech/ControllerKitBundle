# Controller Kit Bundle

[![CI](https://github.com/nowo-tech/ControllerKitBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/ControllerKitBundle/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/controller-kit-bundle.svg?style=flat)](https://packagist.org/packages/nowo-tech/controller-kit-bundle) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/controller-kit-bundle.svg)](https://packagist.org/packages/nowo-tech/controller-kit-bundle) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net) [![Symfony](https://img.shields.io/badge/Symfony-7.4%20%7C%208.0%20%7C%208.1%2B-000000?logo=symfony)](https://symfony.com) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/controller-kit-bundle.svg?style=social&label=Star)](https://github.com/nowo-tech/ControllerKitBundle) [![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** [Install from Packagist](https://packagist.org/packages/nowo-tech/controller-kit-bundle) · Give it a **star** on [GitHub](https://github.com/nowo-tech/ControllerKitBundle) so more developers can find it.

**Controller Kit Bundle** — Utilities for Symfony controllers: **redirectToReferer** (configurable default route) and **SafeForwardTrait** for safe request forwarding, **RequiresValidFormTrait** form guards, and the **`#[MapScopedEntity]`** owner-scoped Doctrine entity argument resolver. Tested on Symfony **7.4**, **8.0**, and **8.1** (also compatible with Symfony 7.0–7.3) · PHP 8.2+ (Symfony 8.x requires PHP 8.4+).

![FrankenPHP Friendly Worker Mode](docs/images/frankenphp-friendly.png)

This bundle is **FrankenPHP worker mode friendly**, including workers that do **not** reset the kernel between requests (no mutable package state). See [docs/FRANKENPHP-WORKER-AUDIT.md](docs/FRANKENPHP-WORKER-AUDIT.md).

## Features

- **RedirectToRefererTrait** — Redirect to the HTTP Referer when valid (same app, route exists), or to a configurable default route. 100% configurable via `nowo_controller_kit.default_route`.
- **SafeForwardTrait** — Forward to another controller method with a check that the method exists (avoids runtime errors).
- **RequiresValidFormTrait** — Form guards for `AbstractController`: `requireValidForm()` / `requireValidCsrfForm()` deny with 403 when a form is not submitted or invalid; `acceptSubmittedForm()` turns an invalid user-facing field into an error flash instead of a 403 (CSRF failures still 403). Needs `symfony/form` (suggested).
- **`#[MapScopedEntity]`** — Controller argument attribute that loads a Doctrine entity with **one query joined on its owner** (another route attribute): another owner's entity is the same 404 as a missing one (no existence leak, no N+1, no manual `getOwner() !== $owner` checks). Nullable arguments get `null`. Needs `doctrine/orm` + `doctrine/doctrine-bundle` (suggested; the resolver is only registered when they are installed).

## Installation

```bash
composer require nowo-tech/controller-kit-bundle
```

With **Symfony Flex**, the recipe registers the bundle and adds config. Without Flex, see [docs/INSTALLATION.md](docs/INSTALLATION.md).

**Manual registration** in `config/bundles.php`:

```php
return [
  // ...
  Nowo\ControllerKitBundle\NowoControllerKitBundle::class => ['all' => true],
];
```

## Requirements

- PHP `>=8.2` (<8.6); **Symfony 8.0** and **8.1** require **PHP 8.4+**
- Symfony **7.4**, **8.0**, or **8.1** (minimum supported minors; also works on Symfony 7.0–7.3 via `composer.json` constraints)
- For traits: `symfony/framework-bundle` in your application (AbstractController)
- For `RequiresValidFormTrait`: `symfony/form` and `symfony/security-core`
- For `#[MapScopedEntity]`: `doctrine/orm` (^2.17 or ^3) and `doctrine/doctrine-bundle`

## Configuration

In `config/packages/nowo_controller_kit.yaml`:

```yaml
nowo_controller_kit:
  default_route: homepage  # Route used when redirectToReferer has no valid Referer
```

Use your own route name (e.g. `app_home`, `dashboard`).

## Usage
### redirectToReferer

Use the trait in a controller that extends `AbstractController`:

```php
use Nowo\ControllerKitBundle\Controller\RedirectToRefererTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class MyController extends AbstractController
{
  use RedirectToRefererTrait;

  public function submit(Request $request): Response
  {
    // ... handle form ...
    return $this->redirectToReferer($request);
    // Optional: merge params and set status
    // return $this->redirectToReferer($request, ['success' => 1], 303);
  }
}
```

When the request has a valid `Referer` header (same host as the current request, path matches a route in your app), the user is redirected there (with path and query params preserved). Otherwise they are redirected to the configured `default_route`.

### safeForward

Use the trait in any controller that has `forward()` (e.g. `AbstractController`):

```php
use Nowo\ControllerKitBundle\Controller\SafeForwardTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
  use SafeForwardTrait;

  public function delegate(): Response
  {
    return $this->safeForward(
      OtherController::class,
      'actionName',
      ['id' => 123],
      ['page' => 1]
    );
  }
}
```

If `OtherController::actionName` does not exist, a `BadMethodCallException` is thrown instead of a generic error.

### requireValidForm / requireValidCsrfForm / acceptSubmittedForm

```php
use Nowo\ControllerKitBundle\Controller\RequiresValidFormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MemberController extends AbstractController
{
  use RequiresValidFormTrait;

  public function delete(Request $request): Response
  {
    $form = $this->createForm(DeleteMemberType::class)->handleRequest($request);
    $this->requireValidCsrfForm($form); // 403 "Invalid CSRF token." when not submitted / invalid
    // ...
  }

  public function changeRole(Request $request): Response
  {
    $form = $this->createForm(MemberRoleType::class)->handleRequest($request);
    if (!$this->acceptSubmittedForm($form, 'role', 'flash.member_invalid_role')) {
      return $this->redirectToRoute('members'); // only "role" was invalid: error flash already added
    }
    // ...
  }
}
```

Requires `symfony/form` and `symfony/security-core` in your application (suggested, not required, by the bundle).

### #[MapScopedEntity]

```php
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;

#[Route('/projects/{projectId}/issues/{issueId}')]
public function show(
  #[MapScopedEntity(parent: 'project', parentField: 'uuid', id: 'issueId', field: 'uuid')]
  Issue $issue, // 404 when missing OR when it belongs to another project
): Response {
  // ...
}
```

`parent` is the owner association on the entity, `parentId` the route attribute with the owner identifier (default `{parent}Id`), `parentField` the owner field it matches (default `id`); `id` / `field` do the same for the entity (default `id` / `id`); `objectManager` picks a named entity manager. See [docs/USAGE.md](docs/USAGE.md#mapscopedentity).

## Demo

- `demo/symfony8` — Symfony **8.1** (FrankenPHP PHP **8.5**), host port **8011** by default

The demo runs **FrankenPHP + Caddy** in Docker with **`APP_ENV=dev`**. Runtime mode is **`FRANKENPHP_MODE`** (`worker` default, or `classic` for per-request PHP) — see [docs/DEMO-FRANKENPHP.md](docs/DEMO-FRANKENPHP.md).

Global demo commands: `make -C demo help` (e.g. `make -C demo up-symfony8`).

## Development

```bash
make up
make install
make test
make cs-check
make phpstan
make igor
make release-check
```

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [PSR evaluation (REQ-CS-007)](docs/PSR.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)

### Additional documentation

- [Demo (FrankenPHP)](docs/DEMO-FRANKENPHP.md)
- [FrankenPHP worker audit (kernel not reset)](docs/FRANKENPHP-WORKER-AUDIT.md)
- [GitHub Actions CI requirements](docs/GITHUB_CI.md)

## Tests and coverage

- Tests: PHPUnit (PHP)
- PHP: 100%

## License and author

MIT · [Nowo.tech](https://nowo.tech) · [Héctor Franco Aceituno](https://github.com/HecFranco)
