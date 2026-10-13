# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/controller-kit-bundle`  
**Last audited**: 2026-10-13

## Bundle entry (classmap)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `NowoControllerKitBundle.php` (repo root) | Bundle bootstrap | FR-CFG-002 |

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
| `DependencyInjection/Configuration.php` | Config tree | FR-CFG-001 |
| `DependencyInjection/ControllerKitExtension.php` | DI extension | FR-CFG-002 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| Bundle entry | 1 | 1 |
| PHP traits | 3 | 3 |
| DI | 2 | 2 |
| Symfony config | 1 | 1 |
| **Total production sources** | **7** | **7** |
