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
