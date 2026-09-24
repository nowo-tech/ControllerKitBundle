# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/controller-kit-bundle` (`symfony-bundle`) |
| Audited revision | `v2.0.10` (this release) |
| Audit date | 2026-09-24 |
| Method | Manual review of every file under `src/` plus `NowoControllerKitBundle.php`; PHPStan with `ruleset-classic` + `ruleset-worker-strict` + `ruleset-hardening` |
| **Verdict** | ✅ **Viable** — no registered services; traits are stateless; safe under kernel **not** reset between requests |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ N/A | The bundle registers no services; the traits declare no properties |
| Static properties / `static` locals | ✅ | None in package source |
| `ResetInterface` / `kernel.reset` coverage | ✅ N/A | Nothing to reset |
| Request / user / locale captured in services | ✅ | `redirectToReferer()` receives the `Request` as an argument and reads the `Referer` header per call |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ✅ | None used; `default_route` is a compiled container parameter |
| Doctrine / EntityManager | ✅ N/A | No persistence |
| Output, headers, `exit`, shutdown functions | ✅ | None; both helpers return `Response` objects |
| Resources (files, sockets, cURL) held open | ✅ | None |
| Memory growth across requests | ✅ | No caches or accumulating arrays |
| Blocking I/O and timeouts | ✅ N/A | No I/O; `forward()` is an in-process sub-request |
| Third-party static state | ✅ | Only Symfony DI/Config at compile time |
| PHPStan FrankenPHP rulesets | ✅ | classic + worker-strict + hardening in `phpstan.neon.dist` |

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| *(none registered)* — `ControllerKitExtension` only sets `nowo_controller_kit.default_route` | — | — | ✅ | ✅ |
| `Controller\RedirectToRefererTrait` | host controller | none; locals only; `getRouter()` resolves `router` from the controller container per call | ✅ | ✅ |
| `Controller\SafeForwardTrait` | host controller | none; locals only | ✅ | ✅ |

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

Re-run this audit when a change adds: a registered service, properties or caches in the traits, an event listener/subscriber, or any use of `$_SERVER` / `$_ENV` / static state at runtime.
