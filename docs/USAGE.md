# Usage

## RedirectToRefererTrait

For controllers extending `Symfony\Bundle\FrameworkBundle\Controller\AbstractController`.

### redirectToReferer

```php
use Nowo\ControllerKitBundle\Controller\RedirectToRefererTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class MyController extends AbstractController
{
    use RedirectToRefererTrait;

    public function afterSubmit(Request $request): RedirectResponse
    {
        // ...
        return $this->redirectToReferer($request);
    }
}
```

**Signature:**

```php
protected function redirectToReferer(
    Request $request,
    ?array $params = [],
    int $status = 302
): RedirectResponse
```

- **$request** — Used to read the `Referer` header.
- **$params** — Optional route parameters to merge (e.g. flash or query). Applied to both referer and default-route redirects.
- **$status** — HTTP status for the redirect (default `302`). Use `303` for POST-redirect-GET if needed.

**Behaviour:**

- If `Referer` is present, its **host matches the current request host** (case-insensitive), and its path (relative to the request base path) matches a **GET** route in your app, redirects to that route (path + query preserved, then merged with `$params`). The route is matched as `GET` even when the current request is a `POST`. Query keys starting with `_` (e.g. `_route`) are ignored.
- Otherwise redirects to the route configured as `nowo_controller_kit.default_route`.

Controllers extending `AbstractController` get the router from the container automatically. Override `getRouter()` only if you do not use `AbstractController`. Do not cache the current `Request` or matched routes on controller properties when running under FrankenPHP worker without kernel reset — see [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).

---

## SafeForwardTrait

For any controller that provides `forward()` (e.g. `AbstractController`).

### safeForward

```php
use Nowo\ControllerKitBundle\Controller\SafeForwardTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    use SafeForwardTrait;

    public function delegate(): Response
    {
        return $this->safeForward(
            OtherController::class,
            'actionName',
            ['id' => 123],
            ['page' => 1]
        );
    }
}
```

**Signature:**

```php
protected function safeForward(
    string $controllerClass,
    string $methodName,
    ?array $pathParams = [],
    ?array $queryParams = []
): JsonResponse|Response
```

- **$controllerClass** — FQCN of the target controller.
- **$methodName** — Name of the method to forward to.
- **$pathParams** — Request/path parameters for the forwarded action.
- **$queryParams** — Query parameters for the forwarded action.

**Behaviour:**

- If the target class has the given method, forwards the request and returns the sub-request response.
- If the method does not exist, throws `BadMethodCallException` with a clear message.

---

## RequiresValidFormTrait

For controllers extending `AbstractController` (uses `createAccessDeniedException()` and `addFlash()`). Requires `symfony/form` and `symfony/security-core` in your application; both are only suggested by the bundle, so hosts that do not use the trait do not need them. Call `handleRequest()` or `submit()` before the helpers.

### requireValidForm

```php
protected function requireValidForm(FormInterface $form, string $message = 'Invalid form submission.'): void
```

Throws `AccessDeniedException` (HTTP 403) with `$message` when the form is not submitted or not valid.

### requireValidCsrfForm

```php
protected function requireValidCsrfForm(FormInterface $form): void
```

Same as `requireValidForm()` with the message `Invalid CSRF token.`. Use it for CSRF-only action forms (delete buttons, toggles) where any error means a bad token.

### acceptSubmittedForm

```php
protected function acceptSubmittedForm(
    FormInterface $form,
    string $field,
    string $invalidFieldFlash,
    string $csrfField = '_token'
): bool
```

```php
$form = $this->createForm(MemberRoleType::class)->handleRequest($request);
if (!$this->acceptSubmittedForm($form, 'role', 'flash.project.member_invalid_role')) {
    return $this->redirectToRoute('project_members', ['id' => $project->getId()]);
}
```

**Behaviour:**

| Form state | Result |
| --- | --- |
| Not submitted | 403 `Invalid form submission.` |
| Valid | `true` |
| CSRF failure | 403 `Invalid CSRF token.` |
| Every error comes from `$field` (or its children) | `addFlash('error', $invalidFieldFlash)` and `false` |
| Any other error (root error, another field, `$field` missing) | 403 `Invalid form submission.` |

A CSRF failure is the error added by Symfony's `CsrfValidationListener` (its cause is a `CsrfToken`) or any error that comes from the `$csrfField` child. Pass `$csrfField` when your app changes `framework.form.csrf_protection.field_name` and renders the token as a real child.

Use it for a field whose bad value is a user error rather than an attack (for example a tampered `ChoiceType` value): the user gets a flash and a redirect instead of a 403 page.

---

## MapScopedEntity

`Nowo\ControllerKitBundle\Attribute\MapScopedEntity` is a controller **argument** attribute. It loads a Doctrine ORM entity from a route attribute with **one query joined on its owner**, matched against another route attribute. A missing entity, an entity that belongs to another owner, and an identifier that is malformed for its column type all answer the **same 404**, so:

- no existence leak across owners (tenants, projects, organizations…);
- no extra query to load the owner and no `if ($entity->getProject() !== $project)` checks in controllers.

Requires `doctrine/orm` (^2.17 or ^3) and `doctrine/doctrine-bundle` in your application (both suggested). The resolver (`Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver`) is registered automatically only when both are installed.

### Signature

```php
#[MapScopedEntity(
    string $parent,                 // owner association on the entity, e.g. 'project'
    ?string $parentId = null,       // route attribute with the owner identifier (default "{$parent}Id")
    string $parentField = 'id',     // owner field matched against it ('id', 'uuid', 'slug', ...)
    string $id = 'id',              // route attribute with the entity identifier
    string $field = 'id',           // entity field matched against it
    ?string $objectManager = null,  // named entity manager (default: the manager of the entity class)
)]
```

### Examples

```php
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;

// /projects/{projectId}/issues/{issueId}  — both identified by UUID
#[Route('/projects/{projectId}/issues/{issueId}', name: 'issue_show')]
public function show(
    #[MapScopedEntity(parent: 'project', parentField: 'uuid', id: 'issueId', field: 'uuid')]
    Issue $issue,
): Response {
    // $issue->getProject() is the route project — guaranteed
}

// /staff/orgs/{orgSlug}/agendas/{agendaId}  — owner by slug, entity by primary key
#[Route('/staff/orgs/{orgSlug}/agendas/{agendaId}')]
public function edit(
    #[MapScopedEntity(parent: 'organization', parentId: 'orgSlug', parentField: 'slug', id: 'agendaId')]
    Agenda $agenda,
): Response {
}

// Optional: a nullable argument receives null instead of a 404
public function preview(
    #[MapScopedEntity(parent: 'project', parentField: 'uuid', id: 'issueId', field: 'uuid')]
    ?Issue $issue,
): Response {
}
```

The query is:

```sql
SELECT e FROM <argument class> e INNER JOIN e.<parent> p
WHERE e.<field> = :id AND p.<parentField> = :parentId
```

Route values are bound with the mapped field types, so UUID columns (binary or native) accept the route string.

### Behaviour

| Situation | Result |
| --- | --- |
| Entity found under the route owner | the entity |
| Entity missing, owned by another owner, route value missing/empty, or identifier malformed for the column type (`ConversionException`) | `NotFoundHttpException` (`"<class>" object not found by "<resolver>".`) — or `null` for a nullable argument |
| Argument not typed with a class, no ORM manager for it, unknown `parent` association, unknown `field` / `parentField` | `LogicException` (mapping error, before any query) |

It scopes the entity to the **route owner** only. Checking that the current user may act on that owner (voters, `#[IsGranted]`, a resolver for the owner itself) stays in your application.

### Application shortcut attribute

The attribute is not final: preset your owner once and keep short call sites.

```php
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class MapProjectScoped extends MapScopedEntity
{
    public function __construct(string $id = 'id', string $field = 'uuid', string $project = 'projectId')
    {
        parent::__construct(parent: 'project', parentId: $project, parentField: 'uuid', id: $id, field: $field);
    }
}

public function show(#[MapProjectScoped] Issue $issue): Response
```

### Overriding the resolver

Define a service with id `Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver` (tagged `controller.targeted_value_resolver`) in your application; the bundle keeps it and does not register its own.
