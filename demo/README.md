# Controller Kit Bundle Demo

This directory contains runnable demos for:

- `symfony7` (http://localhost:8010)
- `symfony8` (http://localhost:8011)

## Quick start

```bash
make up-symfony7
# or
make up-symfony8
```

Each demo includes:

- FrankenPHP with Caddy (HTTP on `:80` inside container)
- Web Profiler enabled in `dev`
- Nowo Twig Inspector enabled in `dev`
- Dedicated `Makefile` per demo (`demo/symfony7/Makefile`, `demo/symfony8/Makefile`)

## Release checks

```bash
make release-check
```

This runs coverage for all demos and verifies startup + HTTP healthcheck.
