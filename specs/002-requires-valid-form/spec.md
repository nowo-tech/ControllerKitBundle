# Feature Specification: RequiresValidFormTrait (form guards)

**Feature Branch**: `002-requires-valid-form`  
**Created**: 2026-10-13  
**Status**: Implemented (v2.1.0)  

**Package**: `nowo-tech/controller-kit-bundle`  
**Origin**: Symfony Beacon spec 137 (FR-002, KIT-REQUIRE-VALID-FORM) — extraction of the host `App\Shared\Controller\RequiresValidFormTrait`.  
**Code inventory**: [`../001-baseline/code-inventory.md`](../001-baseline/code-inventory.md)

---

## Summary

Add `Controller\RequiresValidFormTrait` next to `RedirectToRefererTrait` / `SafeForwardTrait`: helpers for `AbstractController` subclasses that deny (HTTP 403) a form that was not submitted or is invalid, and a variant that turns an invalid user-facing field into an error flash instead of a 403.

---

## User Scenarios

### US-1 — Deny an invalid form (P1)

**Given** a controller uses `RequiresValidFormTrait` and has handled the request, **When** `requireValidForm($form)` runs on a form that is not submitted or invalid, **Then** an `AccessDeniedException` with `Invalid form submission.` (or the given message) is thrown.

### US-2 — Deny a CSRF-only action form (P1)

**Given** a CSRF-only action form, **When** `requireValidCsrfForm($form)` runs and the form is not submitted or invalid, **Then** an `AccessDeniedException` with `Invalid CSRF token.` is thrown.

### US-3 — Flash a tampered field instead of 403 (P1)

**Given** a form with a field such as `role`, **When** `acceptSubmittedForm($form, 'role', 'flash.invalid_role')` runs and every error comes from `role`, **Then** an `error` flash with `flash.invalid_role` is added and `false` is returned; **When** the CSRF token is invalid, the form was not submitted, or another error exists, **Then** a 403 is thrown; **When** the form is valid, **Then** `true` is returned.

---

## Requirements

- **FR-FORM-001**: `requireValidForm(FormInterface $form, string $message = 'Invalid form submission.'): void` throws `$this->createAccessDeniedException($message)` when `!isSubmitted() || !isValid()`.
- **FR-FORM-002**: `requireValidCsrfForm(FormInterface $form): void` behaves like FR-FORM-001 with the message `Invalid CSRF token.`.
- **FR-FORM-003**: `acceptSubmittedForm(FormInterface $form, string $field, string $invalidFieldFlash, string $csrfField = '_token'): bool`:
  - not submitted → 403 `Invalid form submission.`;
  - valid → `true`;
  - CSRF failure → 403 `Invalid CSRF token.`. A CSRF failure is an error whose cause is a `Symfony\Component\Security\Csrf\CsrfToken` (what `CsrfValidationListener` adds to the root form) or an error that comes from the `$csrfField` child;
  - every error comes from `$field` or its children → `addFlash('error', $invalidFieldFlash)`, return `false`;
  - otherwise (root error, other field, `$field` missing) → 403 `Invalid form submission.`.
- **FR-FORM-004**: The CSRF field name is a per-call argument (default `_token`, Symfony's default `framework.form.csrf_protection.field_name`). No bundle configuration key: the trait has no service access beyond `AbstractController`, and the CSRF listener's root-level error is detected without the field name.
- **FR-FORM-005**: `symfony/form` and `symfony/security-core` are `suggest` only; the bundle installs and boots without them. `symfony/security-csrf` is optional (the `instanceof CsrfToken` check is false when the class is absent).
- **FR-FORM-006**: The trait is stateless (no properties or statics) and FrankenPHP worker-safe with the kernel not reset (see `docs/FRANKENPHP-WORKER-AUDIT.md`).

---

## Differences from the Beacon host trait

- `acceptSubmittedRoleForm($form, $flash)` is generalised to `acceptSubmittedForm($form, $field, $flash)` (no default field or flash key).
- The host checked `_token` child validity only; Symfony's CSRF listener adds the error to the root form, so the kit also detects that error by its `CsrfToken` cause.
- The host flashed when the role field was invalid even if other fields were invalid too; the kit flashes only when the named field is the only source of errors, and denies otherwise.

---

## Success Criteria

- **SC-001**: Every branch of the trait is covered by `tests/Unit/Controller/RequiresValidFormTraitTest.php` (test controller extending `AbstractController`), including a real `CsrfExtension` failure.
- **SC-002**: PHPUnit keeps 100% line coverage on `src/`; PHPStan level 8 passes.
- **SC-003**: `make release-check` passes.

---

## Validation

`make release-check` (cs-fix, cs-check, rector-dry, phpstan, igor, test-coverage, demo release-check).
