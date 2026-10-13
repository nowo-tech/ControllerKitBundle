<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Controller;

use LogicException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

use function in_array;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Trait that provides redirectToReferer for controllers extending AbstractController.
 *
 * Redirects to the request Referer when valid (same host, route exists); otherwise
 * redirects to the configurable default route (nowo_controller_kit.default_route).
 *
 * Stateless and FrankenPHP worker-safe (including when the kernel is not reset between
 * requests): no properties, statics, or request data retained on the controller.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait RedirectToRefererTrait
{
    /**
     * Redirects to the referer URL when valid, or to the configured default route.
     *
     * The referer is valid when it is present, non-empty, its host matches the current
     * request host, and its path (relative to the request base path) matches a GET route in the application.
     * Query string and path parameters are preserved and merged with optional $params; query keys starting
     * with `_` are ignored. If the referer is missing or does not match any route,
     * redirects to the default route (config: nowo_controller_kit.default_route).
     *
     * @param Request $request Current request (for Referer header)
     * @param array<string, mixed>|null $params Extra route parameters to merge (e.g. flash or query)
     * @param int $status HTTP status for the redirect (default 302)
     */
    protected function redirectToReferer(Request $request, ?array $params = [], int $status = 302): RedirectResponse
    {
        $referer = $request->headers->get('Referer');

        if (in_array($referer, [null, '', '0'], true)) {
            $defaultRoute = $this->getParameter('nowo_controller_kit.default_route');

            return $this->redirectToRoute($defaultRoute, $params ?? [], $status);
        }

        $parseUrl    = parse_url($referer);
        $refererHost = $parseUrl['host'] ?? '';
        if ($refererHost !== '' && strcasecmp($refererHost, $request->getHost()) !== 0) {
            $defaultRoute = $this->getParameter('nowo_controller_kit.default_route');

            return $this->redirectToRoute($defaultRoute, $params ?? [], $status);
        }

        $path     = $parseUrl['path'] ?? '/';
        $basePath = $request->getBasePath();
        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath));
            $path = $path === '' ? '/' : $path;
        }

        try {
            $router = $this->getRouter();

            // The referer is navigated with GET: match against GET even when the current request is a
            // POST (the router context is shared), and restore the original method afterwards.
            $context        = $router->getContext();
            $originalMethod = $context->getMethod();
            $context->setMethod('GET');

            try {
                $routeInfo = $router->match($path);
            } finally {
                $context->setMethod($originalMethod);
            }

            // Read the route name from the match before merging the query string, and drop
            // reserved (underscore-prefixed) query keys so `?_route=x` cannot redirect elsewhere.
            $routeName = $routeInfo['_route'];

            if (isset($parseUrl['query'])) {
                parse_str($parseUrl['query'], $query);
                foreach ($query as $key => $value) {
                    if (!str_starts_with((string) $key, '_')) {
                        $routeInfo[$key] = $value;
                    }
                }
            }

            $routeParams = [];

            foreach ($routeInfo as $key => $value) {
                if (!in_array($key, ['_route', '_controller', '_locale'], true)) {
                    $routeParams[$key] = $value;
                }
            }

            $routeParams = array_merge($routeParams, $params ?? []);

            return $this->redirectToRoute($routeName, $routeParams, $status);
        } catch (Throwable) {
            $defaultRoute = $this->getParameter('nowo_controller_kit.default_route');

            return $this->redirectToRoute($defaultRoute, $params ?? [], $status);
        }
    }

    /**
     * Resolves the router from the controller service container (AbstractController).
     *
     * Hosts that do not use AbstractController may override this method. Do not memoize
     * per-request match results on controller properties (FrankenPHP worker / no kernel reset).
     */
    protected function getRouter(): RouterInterface
    {
        $container = $this->resolveControllerContainer();
        if ($container !== null && $container->has('router')) {
            $router = $container->get('router');
            if ($router instanceof RouterInterface) {
                return $router;
            }
        }

        throw new LogicException(sprintf('Controller "%s" must expose the "router" service (extend AbstractController) or override getRouter().', static::class));
    }

    private function resolveControllerContainer(): ?ContainerInterface
    {
        if (!property_exists($this, 'container')) {
            return null;
        }

        // Host controllers may type `$container` as ContainerInterface (AbstractController) or leave it untyped.
        $container = $this->container ?? null;

        return $container instanceof ContainerInterface ? $container : null;
    }
}
