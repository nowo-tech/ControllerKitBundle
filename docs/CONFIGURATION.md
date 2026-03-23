# Configuration

All options live under `nowo_controller_kit` in your config.

## default_route

**Type:** `string`  
**Default:** `homepage`

Route name used when `redirectToReferer()` has no valid `Referer` header (missing, empty, or path does not match any route in your application).

Example:

```yaml
nowo_controller_kit:
    default_route: app_home
```

Use the same name you use in `redirectToRoute('app_home')` or in your routing configuration.
