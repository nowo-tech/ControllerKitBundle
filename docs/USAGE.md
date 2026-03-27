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

- If `Referer` is present and its path matches a route in your app, redirects to that route (path + query preserved, then merged with `$params`).
- Otherwise redirects to the route configured as `nowo_controller_kit.default_route`.

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
