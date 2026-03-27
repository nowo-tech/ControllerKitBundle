# Security

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threats and mitigations](#threats-and-mitigations)
- [Dependencies](#dependencies)
- [Reporting a vulnerability](#reporting-a-vulnerability)
- [Supported versions](#supported-versions)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Scope

Controller Kit provides reusable controller helpers and conventions for Symfony applications. Review the bundle’s routes, services, and any abstract controllers for exposure in your app.

## Attack surface

- **HTTP requests** handled by controllers using this kit.
- **Configuration** (services, parameters) loaded from the application.

## Threats and mitigations

| Threat | Mitigation |
|--------|------------|
| Missing authorization in consuming apps | Applications must enforce `access_control`, voters, and roles on routes. |
| XSS in responses | Use Twig escaping; validate user-supplied content. |

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

