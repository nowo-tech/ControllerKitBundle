# Code inventory — 100% traceability

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/controller-kit-bundle`  
**Last audited**: 2026-07-07

## Symfony config (`src/Resources/config/`)

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Resources/config/packages/nowo_controller_kit.yaml` | Default config template | FR-CFG-003 |

## PHP

| Source file | Spec section | Requirement IDs |
| --- | --- | --- |
| `Controller/RedirectToRefererTrait.php` | HTTP controller | FR-CTRL-001 |
| `Controller/SafeForwardTrait.php` | HTTP controller | FR-CTRL-001 |
| `DependencyInjection/Configuration.php` | Config tree | FR-CFG-001 |
| `DependencyInjection/ControllerKitExtension.php` | DI extension | FR-CFG-002 |

## Coverage summary

| Category | Files | Mapped |
| --- | ---: | ---: |
| PHP traits | 2 | 2 |
| Symfony config | 1 | 1 |
| **Total production sources** | **5** | **5** |
