# Security

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threats and mitigations](#threats-and-mitigations)
- [Logging and observability](#logging-and-observability)
- [Dependencies](#dependencies)
- [Reporting a vulnerability](#reporting-a-vulnerability)
- [Supported versions](#supported-versions)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)
- [AI security audit](#ai-security-audit)

## Scope

Controller Kit provides reusable controller helpers and conventions for Symfony applications. Review the bundle’s routes, services, and any abstract controllers for exposure in your app.

## Attack surface

- **HTTP requests** handled by controllers using this kit (`RedirectToRefererTrait`, `SafeForwardTrait`).
- **Configuration** (`nowo_controller_kit.default_route`) loaded from the application.
- No admin UI, no outbound HTTP client, no subprocesses, and no dedicated Monolog channel.

## Threats and mitigations

| Threat | Mitigation |
|--------|------------|
| Open redirect via forged `Referer` | `RedirectToRefererTrait` accepts Referer URLs only when the **host matches** the current request host (case-insensitive) **and** the path matches a known route; otherwise redirects to `nowo_controller_kit.default_route`. |
| Forward to missing controller method | `SafeForwardTrait` validates `method_exists` before `forward()` and throws `BadMethodCallException`. |
| Missing authorization in consuming apps | Applications must enforce `access_control`, voters, and roles on routes. |
| XSS in responses | Use Twig escaping; validate user-supplied content. |

## Logging and observability

This bundle does **not** inject a logger and does **not** write application logs from shipped `src/` code (REQ-OBS-001). Controllers using the traits should rely on the host app’s logging. Integrators **must not** log secrets, session identifiers, or full personal data when wrapping these helpers.

## Dependencies

Run `composer audit`; keep Symfony and this bundle updated.

## Reporting a vulnerability

If you discover a security issue, please report it by email to the maintainers (see [README](../README.md)) or via the issue tracker, and avoid public disclosure until it has been addressed.

## Supported versions

Security fixes are applied to the current major version. Upgrade to the latest release to receive fixes.

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document is current and linked from the README where applicable. |
| **`.gitignore` and `.env`** | `.env` and local env files are ignored; no committed secrets. |
| **No secrets in repo** | No API keys, passwords, or tokens in tracked files. |
| **Recipe / Flex** | Default recipe or installer templates do not ship production secrets. |
| **Input / output** | Inputs validated; outputs escaped in Twig/templates where user-controlled. |
| **Dependencies** | `composer audit` run; issues triaged. |
| **Logging** | Logs do not print secrets, tokens, or session identifiers unnecessarily. |
| **Cryptography** | If used: keys from secure config; never hardcoded. |
| **Permissions / exposure** | Routes and admin features documented; roles configured for production. |
| **Limits / DoS** | Timeouts, size limits, rate limits where applicable. |

Record confirmation in the release PR or tag notes.

## AI security audit

| Field | Value |
|-------|--------|
| **Overall risk** | **Low** (after same-host Referer hardening; residual Medium only if the host misconfigures authorization) |
| **Last review** | 2026-07-27 (Cursor Security Review / standards pass) |
| **Open Critical/High** | None |
| **Residual** | Host apps remain responsible for route authorization and Twig escaping; traits do not replace firewall rules. |

