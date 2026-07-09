# Feature Specification: ControllerKitBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Created**: 2026-07-07  
**Status**: Active  

**Package**: `nowo-tech/controller-kit-bundle`  
**Configuration root**: `nowo_controller_kit`  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

---

## Summary

Minimal Symfony bundle exposing two **traits** for controllers: safe redirect to HTTP Referer with fallback route, and safe forward to another controller method.

---

## User Scenarios

### US-1 — Redirect after form POST (P1)

**Given** a controller uses `RedirectToRefererTrait`, **When** `redirectToReferer()` is called with a valid same-app Referer, **Then** the response redirects to that URL.

### US-2 — Fallback when Referer invalid (P1)

**Given** Referer is missing, cross-host, or not a known route, **When** `redirectToReferer()` runs, **Then** redirect goes to `nowo_controller_kit.default_route`.

### US-3 — Safe forward (P1)

**Given** `SafeForwardTrait`, **When** forwarding to a controller method that exists, **Then** Symfony forward proceeds; **When** method missing, **Then** no fatal error (graceful handling per trait implementation).

---

## Requirements

- **FR-CFG-001**: `Configuration` defines `default_route` (required string).
- **FR-CFG-002**: `ControllerKitExtension` publishes `%nowo_controller_kit.default_route%`.
- **FR-CTRL-001**: `RedirectToRefererTrait` validates Referer (same host, resolvable route), merges extra params, supports status code.
- **FR-CTRL-002**: `SafeForwardTrait` checks `method_exists` before forward.
- **FR-DI-001**: Packaged default YAML template under `Resources/config/packages/`.

---

## Success Criteria

- **SC-001**: **5/5** production files under `src/` mapped in inventory.
- **SC-002**: PHPUnit reports 100% line coverage on `src/`.
- **SC-003**: `composer qa` passes.

---

## Validation

`composer qa`, `vendor/bin/phpunit`, `vendor/bin/phpstan analyse`.
