# Installation

## Composer

```bash
composer require nowo-tech/controller-kit-bundle
```

## Enable the bundle

### With Symfony Flex

The recipe enables the bundle and adds a default config file. Adjust `config/packages/nowo_controller_kit.yaml` to set your `default_route`.

### Without Flex

1. Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Nowo\ControllerKitBundle\NowoControllerKitBundle::class => ['all' => true],
];
```

2. Create `config/packages/nowo_controller_kit.yaml`:

```yaml
nowo_controller_kit:
    default_route: homepage
```

Replace `homepage` with your application’s default route name (e.g. `app_home`, `dashboard`).

## Next steps

- [Configuration](CONFIGURATION.md)
- [Usage](USAGE.md)
