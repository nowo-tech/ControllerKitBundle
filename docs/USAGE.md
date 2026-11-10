# Usage

## Table of contents

- [RedirectToRefererTrait](#redirecttoreferertrait)
  - [redirectToReferer](#redirecttoreferer)
- [SafeForwardTrait](#safeforwardtrait)
  - [safeForward](#safeforward)
- [RequiresValidFormTrait](#requiresvalidformtrait)
  - [requireValidForm](#requirevalidform)
  - [requireValidCsrfForm](#requirevalidcsrfform)
  - [acceptSubmittedForm](#acceptsubmittedform)
- [DownloadResponseTrait](#downloadresponsetrait)
  - [csvDownload](#csvdownload)
  - [jsonDownload](#jsondownload)
  - [Building blocks](#building-blocks)
- [MapScopedEntity](#mapscopedentity)
  - [Signature](#signature)
  - [Examples](#examples)
  - [Behaviour](#behaviour)
  - [Application shortcut attribute](#application-shortcut-attribute)
  - [Owner by plain field](#owner-by-plain-field)
  - [Custom finder (SPI)](#custom-finder-spi)
  - [Overriding the resolver](#overriding-the-resolver)
- [Using it from a DDD / hexagonal application](#using-it-from-a-ddd--hexagonal-application)

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

## DownloadResponseTrait

File downloads for controllers. Works best with `AbstractController` (defaults read from `nowo_controller_kit.downloads`, see [CONFIGURATION.md](CONFIGURATION.md#downloads)); any other class gets the built-in defaults. Stateless (FrankenPHP worker-safe).

### csvDownload

```php
protected function csvDownload(
    string $filename,
    iterable|\Closure $rows,
    array $header = [],
    ?bool $noStore = null,        // null: downloads.no_store
    ?string $delimiter = null,    // null: downloads.csv.delimiter
    ?bool $bom = null,            // null: downloads.csv.bom
    bool $keepNumericStrings = false
): CsvStreamedResponse
```

```php
return $this->csvDownload('remittance-'.$id.'.csv', static function () use ($rows): iterable {
    foreach ($rows as $row) {
        yield [$row->reference, $row->accountName, $row->amount];
    }
}, ['reference', 'account', 'amount'], keepNumericStrings: true);
```

**Behaviour:**

- `Content-Type: text/csv; charset=UTF-8`, `X-Content-Type-Options: nosniff`, `Content-Disposition: attachment` (see [AttachmentDisposition](#building-blocks)), status 200.
- The body is streamed: a closure is only called while the response is sent, so a generator can read the database in batches.
- **Formula injection (OWASP):** every text cell starting with `=`, `+`, `-`, `@`, a tab or a carriage return gets a leading `'`, and a trigger that follows a `;`, `,`, tab, `|` or line break inside the cell gets a space before it (Excel in `;` list-separator locales splits cells there); `int` / `float` values are written as they are (a number is never a formula); `null` is an empty cell; `Stringable` values are guarded like strings.
- `keepNumericStrings: true` leaves plain decimal strings (`-12.50`, `3`) unguarded, for amount columns that must stay numbers; anything else that starts with `-` / `+` (`-1+2`, `+12`) is still guarded.
- Quoting is RFC 4180 (`"` doubled, no escape character).
- An invalid delimiter (not one byte, `"`, CR, LF) throws `InvalidArgumentException` before anything is sent.

### jsonDownload

```php
protected function jsonDownload(array|\JsonSerializable $payload, string $filename, ?bool $noStore = null): JsonAttachmentResponse
```

Pretty-printed JSON (`JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES`, trailing newline), `Content-Type: application/json; charset=UTF-8`, `Content-Disposition: attachment`. A payload that cannot be encoded throws `JsonException`. Pass `noStore: true` for exports that carry secrets or personal data.

### Building blocks

| Class | Use |
| --- | --- |
| `Nowo\ControllerKitBundle\Http\CsvSafeWriter` | `cell($value, $keepNumericStrings)`, `row($row)`, `write($handle, $row, $delimiter)` and the `BOM` constant, for CSV written to a file, a ZIP entry or `php://temp` |
| `Nowo\ControllerKitBundle\Http\CsvStreamedResponse` | The response `csvDownload()` returns (`new CsvStreamedResponse($filename, $rows, $header, bom: …, delimiter: …, noStore: …)`) |
| `Nowo\ControllerKitBundle\Http\JsonAttachmentResponse` | The response `jsonDownload()` returns; also takes `encodingOptions` and extra `headers` |
| `Nowo\ControllerKitBundle\Http\AttachmentDisposition` | `attachment($filename)` / `inline($filename)` header values for any other download (PDF, XLSX, ZIP) |

`AttachmentDisposition` uses `HeaderUtils::makeDisposition()`: a non-ASCII or quoted name keeps its UTF-8 form in `filename*` and gets an ASCII `filename` fallback (non-printable ASCII, `"`, `%`, `/`, `\` → `_`). Control characters, `/` and `\` are replaced in the UTF-8 name too, so a user-controlled name never makes the header throw; an empty or invalid UTF-8 name becomes `download`.

```php
return new Response($pdf, 200, [
    'Content-Type'        => 'application/pdf',
    'Content-Disposition' => AttachmentDisposition::attachment($report->filename.'.pdf'),
]);
```

---

## MapScopedEntity

`Nowo\ControllerKitBundle\Attribute\MapScopedEntity` is a controller **argument** attribute. It loads a Doctrine ORM entity from a route attribute with **one query joined on its owner**, matched against another route attribute. A missing entity, an entity that belongs to another owner, and an identifier that is malformed for its column type all answer the **same 404**, so:

- no existence leak across owners (tenants, projects, organizations…);
- no extra query to load the owner and no `if ($entity->getProject() !== $project)` checks in controllers.

Requires `doctrine/orm` (^2.17 or ^3) and `doctrine/doctrine-bundle` in your application (both suggested). The resolver (`Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver`) is registered automatically when both are installed, or (2.4+) when you register a [custom finder](#custom-finder-spi).

### Signature

```php
#[MapScopedEntity(
    string $parent,                 // owner association on the entity, e.g. 'project' (2.4+: or a field holding the owner id)
    ?string $parentId = null,       // route attribute with the owner identifier (default "{$parent}Id")
    string $parentField = 'id',     // owner field matched against it ('id', 'uuid', 'slug', ...); unused for a field owner
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
| Argument not typed with a class, no ORM manager for it, `parent` neither an association nor a field, unknown `field` / `parentField`, a field owner that scopes nothing (see [Owner by plain field](#owner-by-plain-field)) | `LogicException` (mapping error, before any query) |
| No Doctrine and no finder that supports the class | `LogicException` |
| A finder returns an object of another class | `LogicException` |

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

### Owner by plain field

Since 2.4. When the entity keeps its owner as an id **value** (a plain column, no association), name that field as
`parent`. The resolver matches it directly, without a join, and ignores `parentField`. Set `parentId` to the route
attribute that holds the owner id: the default is `{$parent}Id`, so `parent: 'projectId'` alone would read a
`projectIdId` route attribute and answer 404 every time.

```php
// Issue::$projectId is a string column holding the project UUID
#[Route('/projects/{projectId}/issues/{issueId}')]
public function show(
    #[MapScopedEntity(parent: 'projectId', parentId: 'projectId', id: 'issueId', field: 'uuid')]
    Issue $issue,
): Response {
}
```

```sql
SELECT e FROM <argument class> e WHERE e.<field> = :id AND e.<parent> = :parentId
```

Both values are bound with the entity's mapped field types. If `parent` is both an association and a field name, the
association wins (the 2.3 behaviour).

The owner field must hold the owner's id and be **set by the server**: there is no foreign key, so if users can write
that column they can move a row into another owner's scope. Configurations that would scope nothing are refused with
a `LogicException`: `parent` is the entity's identifier, `parent` equals `field`, or the owner and the entity read
the same route attribute. `parent` must be a plain mapped column (embeddables are not accepted).

### Custom finder (SPI)

Since 2.4. To load an argument from something other than the built-in Doctrine query (a repository port, a read
model, an API), implement `Nowo\ControllerKitBundle\ValueResolver\ScopedEntityFinderInterface`:

```php
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityFinderInterface;

final class IssueViewFinder implements ScopedEntityFinderInterface
{
    public function __construct(private IssueQueries $issues) {}

    public function supports(string $class, MapScopedEntity $attribute): bool
    {
        return $class === IssueView::class;
    }

    public function findScoped(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute): ?object
    {
        // ONE lookup filtered by BOTH ids: this IS the owner check.
        return $this->issues->findInProject((string) $ownerId, (string) $id);
    }
}
```

> **Your finder is the owner check.** Filter by `$ownerId` in the same lookup that loads the object. Never load by
> `$id` alone and return it, never fall back to an unscoped lookup, and return `null` for an object of another
> owner. The bundle does not check the owner again: a finder that skips it is a cross-tenant read (IDOR).

How finders are picked:

- Finders are services tagged `nowo_controller_kit.scoped_entity_finder` (`ScopedEntityFinderInterface::TAG`). With
  `autoconfigure: true` (the default in `services.yaml`) implementing the interface is enough. Set an order with a
  `priority` tag attribute or `#[AsTaggedItem(priority: …)]`, highest first. Register them through the tag, not from a
  compiler pass of your own that runs late: the bundle collects the tag before FrameworkBundle builds the resolvers.
- For each argument the finders are asked in order; the **first** whose `supports()` returns true decides (its `null`
  is a 404, the next finders are not asked). `supports()` must not query or throw.
- When none supports the class, the built-in Doctrine query runs, as before 2.4, with its mapping checks.
- `findScoped()` is called only with non-empty string or int route values. Return `null` for a missing object, an
  object of another owner and a malformed id alike: the resolver answers the same 404 (or `null` for a nullable
  argument). An object that is not an instance of the argument class is a `LogicException` (a bug in the finder,
  reported loudly instead of a `TypeError`).
- Without Doctrine, the resolver is registered as soon as one finder is tagged.

The built-in query is `Nowo\ControllerKitBundle\ValueResolver\DoctrineScopedEntityFinder` (registered with Doctrine,
not tagged). A finder can delegate to it, for example to load the entity owner-scoped and map it to a view:

```php
public function __construct(private DoctrineScopedEntityFinder $doctrine) {}

public function findScoped(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute): ?object
{
    $issue = $this->doctrine->findScoped(Issue::class, $id, $ownerId, $attribute);

    return $issue instanceof Issue ? IssueView::fromIssue($issue) : null;
}
```

**Route values.** Use route requirements for both ids (`requirements: ['projectId' => Requirement::UUID, 'issueId' =>
'\d+']`). Integer columns cast route strings, so `1abc`, ` 1`, `01` and `1.0` all match id 1 without a requirement,
and a finder receives the raw string. Authorise the owner on the same normalised value you look it up with.

### Overriding the resolver

Define a service with id `Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver` (tagged `controller.targeted_value_resolver`) in your application; the bundle keeps it and does not register its own.

## Using it from a DDD / hexagonal application

**Layer:** presentation. The traits (`RedirectToRefererTrait`, `SafeForwardTrait`, `RequiresValidFormTrait`,
`DownloadResponseTrait`) and response helpers (`CsvStreamedResponse`, `JsonAttachmentResponse`, `CsvSafeWriter`,
`AttachmentDisposition`) belong in your controllers. They depend only on Symfony HTTP and form types, never on
your entities. The bundle dispatches no events and stores nothing.

**Keeping the domain clean**

- Feed `csvDownload()` / `jsonDownload()` from read models or query DTOs (iterables of scalar rows,
  `JsonSerializable` views) rather than from entities.
- `#[MapScopedEntity]` is an optional Doctrine ORM adapter. It loads an entity owned by the route owner in one
  query and returns 404 otherwise. The owner can be an association or (2.4+) a plain field holding the owner id,
  as in a model that references aggregates by id. To load read models or objects from a non-Doctrine repository,
  implement the [`ScopedEntityFinderInterface`](#custom-finder-spi) SPI (your finder is then the owner check). Or, in a strict hexagonal controller,
  take the route ids as scalars and call your application query instead:

```php
#[Route('/projects/{projectId}/issues/{issueId}')]
public function show(string $projectId, string $issueId, IssueQueries $issues): Response
{
    $issue = $issues->findInProject(ProjectId::fromString($projectId), IssueId::fromString($issueId))
        ?? throw $this->createNotFoundException();
    // ...
}
```

**Known limitations**

- A finder is the owner check for the classes it supports; the bundle cannot verify it. Filter by the owner id in the
  same lookup.
- Finders are chosen by class (`supports()`), not per attribute.
