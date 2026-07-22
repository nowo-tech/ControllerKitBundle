# Demo with FrankenPHP

This bundle includes a runnable demo with FrankenPHP in:

- `demo/symfony8` — Symfony **8.1** (PHP **8.4+**)

The demo uses:

- Caddy on HTTP `:80` inside the container
- **`Caddyfile`**: **worker** mode (`php_server { worker ... }`) for throughput
- **`Caddyfile.dev`**: classic `php_server` (**no worker**) — hot-reload friendly

Runtime selection is via **`FRANKENPHP_MODE`** (`classic` | `worker`), not `APP_ENV`. Compose still sets **`APP_ENV=dev`** / **`APP_DEBUG=1`** and mounts **`docker/php-dev.ini`**.

## Quick start

From the bundle root:

```bash
make -C demo up-symfony8
```

Then open:

- Symfony 8.1: `http://localhost:8011`

## Switching classic vs worker (`FRANKENPHP_MODE`)

| Value | Behaviour |
| --- | --- |
| **`worker`** (default) | Use the worker Caddyfile |
| **`classic`** | Entrypoint copies `Caddyfile.dev` (plain `php_server`) |

Set in `.env` / `.env.example`. Compose passes `FRANKENPHP_MODE=${FRANKENPHP_MODE:-worker}` into the PHP service. After changing `.env`, run `docker compose up -d` (or `make up`) so the container is **recreated** — a plain `restart` does not reload env. No image rebuild is required.

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
