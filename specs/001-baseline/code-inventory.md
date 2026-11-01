# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/controller-kit-bundle`  
**Last audited**: 2026-11-01

## Bundle entry (classmap)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `NowoControllerKitBundle.php` (repo root) | Bundle bootstrap (+ compiler pass, [spec 003](../003-map-scoped-entity/spec.md)) | FR-CFG-002, FR-SCOPE-006 |

## Symfony config (`src/Resources/config/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/config/packages/nowo_controller_kit.yaml` | Default config template | FR-CFG-003 |

## PHP

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Controller/RedirectToRefererTrait.php` | HTTP controller | FR-CTRL-001 |
| `Controller/SafeForwardTrait.php` | HTTP controller | FR-CTRL-001 |
| `Controller/RequiresValidFormTrait.php` | HTTP controller ([spec 002](../002-requires-valid-form/spec.md)) | FR-FORM-001..003 |
| `Attribute/MapScopedEntity.php` | Controller argument attribute ([spec 003](../003-map-scoped-entity/spec.md)) | FR-SCOPE-001 |
| `ValueResolver/ScopedEntityValueResolver.php` | Targeted value resolver ([spec 003](../003-map-scoped-entity/spec.md)) | FR-SCOPE-002..005, FR-SCOPE-007 |
| `DependencyInjection/Compiler/ScopedEntityValueResolverPass.php` | Conditional registration ([spec 003](../003-map-scoped-entity/spec.md)) | FR-SCOPE-006 |
| `DependencyInjection/Configuration.php` | Config tree (`default_route`, `downloads.*`) | FR-CFG-001, FR-DL-006 |
| `DependencyInjection/ControllerKitExtension.php` | DI extension | FR-CFG-002 |

## HTTP downloads (`src/Controller/`, `src/Http/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Controller/DownloadResponseTrait.php` | Controller helpers `csvDownload()` / `jsonDownload()` | FR-DL-001, FR-DL-006 |
| `Http/AttachmentDisposition.php` | RFC 6266 `Content-Disposition` with sanitised UTF-8 name + ASCII fallback | FR-DL-002 |
| `Http/CsvSafeWriter.php` | Formula-injection-safe CSV cells (leading trigger + trigger after an embedded separator) | FR-DL-003 |
| `Http/CsvStreamedResponse.php` | Streamed CSV attachment (BOM, delimiter, `nosniff`, optional `no-store`) | FR-DL-004 |
| `Http/JsonAttachmentResponse.php` | JSON attachment (`nosniff`, optional `no-store`) | FR-DL-005 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Bundle entry | 1 | 1 |
| PHP traits (redirect, forward, form) | 3 | 3 |
| Attribute / value resolver | 2 | 2 |
| DI | 3 | 3 |
| HTTP downloads (trait + `Http/`) | 5 | 5 |
| Symfony config | 1 | 1 |
| **Total production sources** | **15** | **15** |

Audit command: `git ls-files src | wc -l` (14) + root `NowoControllerKitBundle.php` (1) = 15.
