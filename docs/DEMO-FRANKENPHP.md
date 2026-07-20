# Demo with FrankenPHP

This bundle includes a runnable demo with FrankenPHP in:

- `demo/symfony8` — Symfony **8.1** (PHP **8.4+**)

The demo uses:

- Caddy on HTTP `:80` inside the container
- **`Caddyfile`** (production image / `APP_ENV` not `dev`): **worker** mode for throughput
- **`Caddyfile.dev`**: classic `php_server` (**no worker**) — used when the entrypoint runs with **`APP_ENV=dev`** (default in `docker-compose`)

**Default development stack:** `docker-compose.yml` sets **`APP_ENV=dev`** and **`APP_DEBUG=1`**, mounts **`docker/frankenphp/Caddyfile.dev`** and **`docker/php-dev.ini`**. The container entrypoint copies `Caddyfile.dev` over the active Caddyfile so you get **one PHP process per request**, not workers.

## Quick start

From the bundle root:

```bash
make -C demo up-symfony8
```

Then open:

- Symfony 8.1: `http://localhost:8011`

## Development stack in demos

The demo includes:

- **Symfony Debug** (`symfony/debug-bundle`)
- **Symfony Web Profiler** (`symfony/web-profiler-bundle`)
- **`APP_DEBUG=1`** in `.env.example`
- **Nowo Twig Inspector** (`nowo-tech/twig-inspector-bundle`)

Example `config/bundles.php`:

```php
<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class     => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class               => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class             => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class   => ['dev' => true, 'test' => true],
    Nowo\ControllerKitBundle\NowoControllerKitBundle::class   => ['all' => true],
];
```

## Troubleshooting

- If app does not respond, run `make -C demo/symfony8 logs`.
- If routes/config changed, run `make -C demo/symfony8 cache-clear`.
- If dependencies are outdated, run `make -C demo/symfony8 update-bundle`.
