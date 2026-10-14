# Feature Specification: `#[MapScopedEntity]` owner-scoped entity argument resolver

**Feature Branch**: `003-map-scoped-entity`  
**Created**: 2026-10-14  
**Status**: Implemented (v2.2.0)  

**Package**: `nowo-tech/controller-kit-bundle`  
**Origin**: Symfony Beacon `docs/ops/KIT-EXTRACTION.md` row #22 — extraction of the host `App\Project\Http\MapProjectScoped` + `ProjectScopedEntityValueResolver` (Beacon spec 136, DUP-SCOPE).  
**Code inventory**: [`../001-baseline/code-inventory.md`](../001-baseline/code-inventory.md)

---

## Summary

Add a controller argument attribute `Attribute\MapScopedEntity` and its targeted value resolver `ValueResolver\ScopedEntityValueResolver`. The resolver loads a Doctrine ORM entity from one route attribute with **one query** joined on its owner association, matched against another route attribute. A missing entity, an entity of another owner and a malformed identifier are the same 404, so controllers need no `getOwner() !== $owner` checks, there is no existence leak across owners, and the owner is not loaded separately (no N+1).

---

## User Scenarios

### US-1 — Child entity under its owner (P1)

**Given** `#[Route('/projects/{projectId}/issues/{issueId}')]` and an argument `#[MapScopedEntity(parent: 'project', parentField: 'uuid', id: 'issueId', field: 'uuid')] Issue $issue`, **When** the issue belongs to the route project, **Then** the controller receives it.

### US-2 — Another owner's entity is a 404 (P1)

**Given** the same route, **When** the issue exists but belongs to another project, or does not exist, or the identifier is malformed for its column type, **Then** a `NotFoundHttpException` (`"<class>" object not found by "<resolver>".`) is thrown — the same answer in every case.

### US-3 — Optional argument (P2)

**Given** a nullable argument (`?Issue $issue`), **When** the entity is not found (or a route value is missing), **Then** the argument is `null` instead of a 404.

### US-4 — Host shortcut attribute (P2)

**Given** an application subclass such as `final class MapProjectScoped extends MapScopedEntity` that presets the owner, **When** it is used on an argument, **Then** the bundle resolver handles it (attributes are matched with `IS_INSTANCEOF`).

---

## Requirements

- **FR-SCOPE-001**: `MapScopedEntity(string $parent, ?string $parentId = null, string $parentField = 'id', string $id = 'id', string $field = 'id', ?string $objectManager = null)` extends Symfony's `ValueResolver` attribute and targets `ScopedEntityValueResolver`. `$parentId` defaults to `"{$parent}Id"` (`getParentRouteAttribute()`). The class is not final so hosts can preset an owner.
- **FR-SCOPE-002**: The resolver runs one DQL query: `SELECT e FROM <argument type> e INNER JOIN e.<parent> p WHERE e.<field> = :id AND p.<parentField> = :parentId`, with parameters typed from the mapped field types (so UUID/binary columns convert the route string).
- **FR-SCOPE-003**: Not found, another owner, a missing / empty / non-scalar route value, or a DBAL `ConversionException` for the identifier → `NotFoundHttpException`; for a nullable argument → `null`.
- **FR-SCOPE-004**: Configuration errors are `LogicException`s raised before querying: argument not typed with a class; no ORM entity manager for the class (or the named `objectManager`); unknown `$parent` association; unknown `$field` on the entity; unknown `$parentField` on the owner. Field names therefore never reach DQL unchecked.
- **FR-SCOPE-005**: The entity manager is `ManagerRegistry::getManagerForClass(<type>)` or `getManager($objectManager)` — any entity manager works.
- **FR-SCOPE-006**: `doctrine/orm` and `doctrine/doctrine-bundle` are `suggest` only. `DependencyInjection\Compiler\ScopedEntityValueResolverPass` registers the resolver (private, tag `controller.targeted_value_resolver`) only when `Doctrine\ORM\EntityManagerInterface` exists and the container has the `doctrine` registry; it runs before FrameworkBundle's `ControllerArgumentValueResolverPass` and keeps an application-defined service with the same id.
- **FR-SCOPE-007**: The resolver is stateless (readonly, registry only) and FrankenPHP worker-safe with the kernel not reset.

---

## Mapping from the Beacon host

| Beacon | Bundle |
| --- | --- |
| `#[MapProjectScoped]` (`{id}` uuid under `{projectId}` uuid) | `#[MapScopedEntity(parent: 'project', parentField: 'uuid', field: 'uuid')]` |
| `#[MapProjectScoped(id: 'groupAccessId')]` | `#[MapScopedEntity(parent: 'project', parentField: 'uuid', id: 'groupAccessId', field: 'uuid')]` |
| `#[MapProjectScoped(id: 'keyId', field: 'id')]` | `#[MapScopedEntity(parent: 'project', parentField: 'uuid', id: 'keyId')]` |
| `project:` (route attribute) | `parentId:` |

Or keep the host name: `final class MapProjectScoped extends MapScopedEntity` whose constructor calls `parent::__construct(parent: 'project', parentId: $project, parentField: 'uuid', id: $id, field: $field)`; the host resolver class can then be deleted.

Differences: parameters are typed from the mapped field types (Beacon bound untyped strings); malformed identifiers are 404 instead of a DBAL error; nullable arguments are supported; mapping errors are reported with explicit messages.

## Other hosts

- **open-agendesk-v2** — manual `$entity->getOrganization()->getId() !== $organization->getId()` checks under `/staff/orgs/{orgSlug}/...` become `#[MapScopedEntity(parent: 'organization', parentId: 'orgSlug', parentField: 'slug', id: 'agendaId')] Agenda $agenda`.
- **podologiapriego-crm** — `BusinessScopeValueResolver` builds a non-entity value object (`BusinessScope`) for the owner itself and checks it is enabled; that is not a child entity, so it stays in the host. Child entities under `{businessMachineName}` can use `#[MapScopedEntity(parent: 'business', parentId: 'businessMachineName', parentField: 'machineName', …)]`.

---

## Success Criteria

- **SC-001**: `tests/Unit/ValueResolver/ScopedEntityValueResolverTest.php` covers every branch against a real in-memory SQLite entity manager (`Owner` / `Item` fixtures), including the single-query assertion and a `ConversionException` column type.
- **SC-002**: `tests/Integration/ValueResolver/ScopedEntityValueResolverKernelTest.php` boots FrameworkBundle + DoctrineBundle + this bundle and resolves arguments through the real HTTP kernel (own owner → 200, another owner / missing / malformed → 404, nullable → `null`).
- **SC-003**: 100% line coverage on `src/`; PHPStan level 8; `make release-check` passes.

---

## Validation

`make release-check` (cs-fix, cs-check, rector-dry, phpstan, igor, test-coverage, demo release-check).
