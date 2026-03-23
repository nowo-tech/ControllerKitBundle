# Demo with FrankenPHP

This bundle includes runnable demos with FrankenPHP in:

- `demo/symfony7`
- `demo/symfony8`

Each demo uses:

- Caddy on HTTP `:80` inside the container
- `Caddyfile` (worker mode) for non-dev usage
- `Caddyfile.dev` (classic mode, no worker) for development

## Quick start

From the bundle root:

```bash
make -C demo up-symfony7
# or
make -C demo up-symfony8
```

Then open:

- Symfony 7: `http://localhost:8010`
- Symfony 8: `http://localhost:8011`

## Development stack in demos

Both demos include:

- `symfony/web-profiler-bundle`
- `APP_DEBUG=1` in `.env.example`
- `nowo-tech/twig-inspector-bundle`

## Troubleshooting

- If app does not respond, run `make -C demo/symfony7 logs` or `make -C demo/symfony8 logs`.
- If routes/config changed, run `make -C demo/symfony7 cache-clear` (or `symfony8`).
- If dependencies are outdated, run `make -C demo/symfony7 update-bundle` (or `symfony8`).
