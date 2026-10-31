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

## downloads

Defaults of `DownloadResponseTrait::csvDownload()` and `jsonDownload()`. Each call can override them with an explicit argument; a controller that does not extend `AbstractController` uses the built-in defaults below.

| Key | Type | Default | Role |
| --- | --- | --- | --- |
| `downloads.no_store` | `bool` | `false` | Send `Cache-Control: no-store, private` (exports with secrets or personal data) |
| `downloads.csv.delimiter` | `string` | `,` | One single-byte field delimiter (`,`, `;`, a tab); a double quote or a line break is rejected |
| `downloads.csv.bom` | `bool` | `true` | Write the UTF-8 byte order mark first (Excel then reads the file as UTF-8) |

Example (European spreadsheets, private exports):

```yaml
nowo_controller_kit:
    downloads:
        no_store: true
        csv:
            delimiter: ';'
            bom: true
```

Container parameters: `nowo_controller_kit.downloads.no_store`, `nowo_controller_kit.downloads.csv_delimiter`, `nowo_controller_kit.downloads.csv_bom`.
