<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

use function in_array;

/**
 * Trait that provides redirectToReferer for controllers extending AbstractController.
 *
 * Redirects to the request Referer when valid (same app, route exists); otherwise
 * redirects to the configurable default route (nowo_controller_kit.default_route).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait RedirectToRefererTrait
{
    /**
     * Redirects to the referer URL when valid, or to the configured default route.
     *
     * The referer is valid when it is present, non-empty, and its path matches a route
     * in the application. Query string and path parameters are preserved and merged
     * with optional $params. If the referer is missing or does not match any route,
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

        $parseUrl = parse_url($referer);
        $path     = $parseUrl['path'] ?? '/';

        try {
            $router    = $this->getRouter();
            $routeInfo = $router->match($path);

            if (isset($parseUrl['query'])) {
                parse_str($parseUrl['query'], $query);
                $routeInfo = array_merge($routeInfo, $query);
            }

            $routeName   = $routeInfo['_route'];
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
}
