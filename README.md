# Controller Kit Bundle

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net) [![Symfony](https://img.shields.io/badge/Symfony-6%20%7C%207%20%7C%208-000000?logo=symfony)](https://symfony.com)

**Controller Kit Bundle** — Utilities for Symfony controllers: **redirectToReferer** (configurable default route) and **SafeForwardTrait** for safe request forwarding. For Symfony 6, 7 and 8 · PHP 8.1+.

## Features

- **RedirectToRefererTrait** — Redirect to the HTTP Referer when valid (same app, route exists), or to a configurable default route. 100% configurable via `nowo_controller_kit.default_route`.
- **SafeForwardTrait** — Forward to another controller method with a check that the method exists (avoids runtime errors).

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

## Configuration

In `config/packages/nowo_controller_kit.yaml`:

```yaml
nowo_controller_kit:
    default_route: homepage   # Route used when redirectToReferer has no valid Referer
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

When the request has a valid `Referer` header whose path matches a route in your app, the user is redirected there (with path and query params preserved). Otherwise they are redirected to the configured `default_route`.

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

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)

## Requirements

- PHP 8.1+
- Symfony 6.0 / 7.0 / 8.0
- For traits: `symfony/framework-bundle` in your application (AbstractController)

## Development

```bash
make up
make install
make test
make cs-check
make phpstan
make release-check
```

## License and author

MIT · [Nowo.tech](https://nowo.tech) · [Héctor Franco Aceituno](https://github.com/HecFranco)
