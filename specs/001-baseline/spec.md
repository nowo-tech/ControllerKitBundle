# Feature Specification: ControllerKitBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Created**: 2026-07-07  
**Status**: Active  

**Package**: `nowo-tech/controller-kit-bundle`  
**Configuration root**: `nowo_controller_kit`  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

---

## Summary

Small Symfony bundle of controller helpers: safe redirect to the HTTP Referer with a fallback route, safe forward to another controller method, `RequiresValidFormTrait` ([spec 002](../002-requires-valid-form/spec.md)), `#[MapScopedEntity]` ([spec 003](../003-map-scoped-entity/spec.md)), and safe file downloads (`DownloadResponseTrait`: CSV with formula-injection guard, JSON attachments, RFC 6266 filenames).

---

## User Scenarios

### US-1 — Redirect after form POST (P1)

**Given** a controller uses `RedirectToRefererTrait`, **When** `redirectToReferer()` is called with a valid same-app Referer, **Then** the response redirects to that URL.

### US-2 — Fallback when Referer invalid (P1)

**Given** Referer is missing, cross-host, or not a known route, **When** `redirectToReferer()` runs, **Then** redirect goes to `nowo_controller_kit.default_route`.

### US-3 — Safe forward (P1)

**Given** `SafeForwardTrait`, **When** forwarding to a controller method that exists, **Then** Symfony forward proceeds; **When** method missing, **Then** no fatal error (graceful handling per trait implementation).

### US-4 — Safe CSV / JSON download (P1)

**Given** a controller uses `DownloadResponseTrait`, **When** `csvDownload($rows, $filename)` returns, **Then** the response streams a UTF-8 CSV attachment (BOM and delimiter from `nowo_controller_kit.downloads.csv`) in which a cell that starts with `=`, `+`, `-`, `@`, tab or CR, or has such a character after an embedded `;`, `,`, tab, `|` or line break, is neutralised; the filename is sanitised and sent with an ASCII fallback; `X-Content-Type-Options: nosniff` is set and, with `no_store`, `Cache-Control: no-store, private`. **When** `jsonDownload($payload, $filename)` returns, **Then** the same headers apply to a JSON attachment.

---

## Requirements

- **FR-CFG-001**: `Configuration` defines `default_route` (required string).
- **FR-CFG-002**: `ControllerKitExtension` publishes `%nowo_controller_kit.default_route%`.
- **FR-CTRL-001**: `RedirectToRefererTrait` validates Referer (same host, resolvable route), merges extra params, supports status code.
- **FR-CTRL-002**: `SafeForwardTrait` checks `method_exists` before forward.
- **FR-DI-001**: Packaged default YAML template under `Resources/config/packages/`.
- **FR-CFG-003**: The packaged default config template documents every key (`default_route`, `downloads.*`).
- **FR-DL-001**: `DownloadResponseTrait::csvDownload()` / `jsonDownload()` build the download responses from the call arguments, falling back to the `nowo_controller_kit.downloads.*` parameters.
- **FR-DL-002**: `AttachmentDisposition` builds `attachment` / `inline` headers (RFC 6266) from a sanitised UTF-8 filename (control characters, `/` and `\` become `_`; `download` when empty or not UTF-8) with an ASCII `filename` fallback (non-printable ASCII, `"`, `%`, `/`, `\` become `_`).
- **FR-DL-003**: `CsvSafeWriter` prefixes `'` to a cell that starts with a formula trigger and inserts a space after an embedded separator followed by a trigger (`SEPARATOR_FORMULA_PATTERN`); numeric strings can be kept; the delimiter is validated.
- **FR-DL-004**: `CsvStreamedResponse` streams rows through `CsvSafeWriter` with optional BOM, `text/csv; charset=UTF-8`, `nosniff` and optional `no-store, private`.
- **FR-DL-005**: `JsonAttachmentResponse` encodes the payload (`JSON_THROW_ON_ERROR`) as an `application/json` attachment with `nosniff` and optional `no-store, private`.
- **FR-DL-006**: `downloads.no_store` (default false), `downloads.csv.delimiter` (one byte, not `"` / CR / LF; default `,`) and `downloads.csv.bom` (default true) are validated config.
- **FR-WRK-001**: Package source stays FrankenPHP worker-safe with **kernel not reset** between requests (no services, no mutable trait state, no static/request leakage); documented in `docs/FRANKENPHP-WORKER-AUDIT.md`.

---

## Success Criteria

- **SC-001**: **15/15** production sources mapped in inventory (incl. root `NowoControllerKitBundle.php`).
- **SC-002**: PHPUnit reports 100% line coverage on `src/`.
- **SC-003**: `composer qa` passes.

---

## Validation

`composer qa`, `vendor/bin/phpunit`, `vendor/bin/phpstan analyse`.
