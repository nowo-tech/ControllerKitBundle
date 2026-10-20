# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/controller-kit-bundle` (`symfony-bundle`) |
| Audited revision | `v2.2.0` (this release) |
| Audit date | 2026-10-14 |
| Method | Manual review of every file under `src/` plus `NowoControllerKitBundle.php`; PHPStan with `ruleset-classic` + `ruleset-worker-strict` + `ruleset-hardening` |
| **Verdict** | ✅ **Viable** — one optional readonly service (`ScopedEntityValueResolver`); traits are stateless; safe under kernel **not** reset between requests |

## Table of contents

- [Execution model assumed](#execution-model-assumed)
- [Summary](#summary)
- [Services reviewed](#services-reviewed)
- [Findings](#findings)
  - [Fixed in this release](#fixed-in-this-release)
- [Usage recommendations in worker mode](#usage-recommendations-in-worker-mode)
- [Re-audit triggers](#re-audit-triggers)

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | One optional service (`ScopedEntityValueResolver`, readonly, Doctrine registry only); the traits declare no properties |
| Static properties / `static` locals | ✅ | None in package source |
| `ResetInterface` / `kernel.reset` coverage | ✅ N/A | Nothing to reset |
| Request / user / locale captured in services | ✅ | `redirectToReferer()` receives the `Request` as an argument and reads the `Referer` header per call |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ✅ | None used; `default_route` is a compiled container parameter |
| Doctrine / EntityManager | ✅ | `ScopedEntityValueResolver` reads through the registry per call (manager resolved per argument; no entity kept) |
| Output, headers, `exit`, shutdown functions | ✅ | None; both helpers return `Response` objects |
| Resources (files, sockets, cURL) held open | ✅ | None |
| Memory growth across requests | ✅ | No caches or accumulating arrays |
| Blocking I/O and timeouts | ✅ N/A | No I/O; `forward()` is an in-process sub-request |
| Third-party static state | ✅ | Only Symfony DI/Config at compile time |
| PHPStan FrankenPHP rulesets | ✅ | classic + worker-strict + hardening in `phpstan.neon.dist` |

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `ControllerKitExtension` — only sets `nowo_controller_kit.default_route` | — | — | ✅ | ✅ |
| `Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver` (only with doctrine/orm + DoctrineBundle) | yes | none (`final readonly`, `ManagerRegistry` only; entity returned, never stored) | ✅ | ✅ |
| `Controller\RedirectToRefererTrait` | host controller | none; locals only; `getRouter()` resolves `router` from the controller container per call | ✅ | ✅ |
| `Controller\SafeForwardTrait` | host controller | none; locals only | ✅ | ✅ |
| `Controller\RequiresValidFormTrait` | host controller | none; locals only (form passed per call; flash goes to the request session) | ✅ | ✅ |

`RouterListener` refreshes the router `RequestContext` on every main request, so `$router->match($path)` does not use a stale host/scheme across worker requests.

## Findings

No worker-mode findings under scenario **B** (kernel not reset).

### Fixed in this release

Previously `getRouter()` was only supplied by unit-test doubles; `AbstractController` does not define it, so production controllers fell through `catch (Throwable)` to `default_route`. The trait now resolves the `router` service from the controller container (override `getRouter()` if you do not use `AbstractController`).

## Usage recommendations in worker mode

- No special configuration or reset hook is needed for this package.
- Host controllers that use these traits must stay **stateless**: do not store the `Request`, Referer, or matched route parameters in controller properties (controllers are shared services when the kernel is not reset).
- Prefer `AbstractController` so `getRouter()` / `getParameter()` / `redirectToRoute()` / `forward()` work without custom wiring.

## Re-audit triggers

Re-run this audit when a change adds: another registered service (or state in `ScopedEntityValueResolver`), properties or caches in the traits, an event listener/subscriber, or any use of `$_SERVER` / `$_ENV` / static state at runtime.
