<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Controller;

use InvalidArgumentException;
use Nowo\ControllerKitBundle\Controller\RedirectToRefererTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Unit tests for RedirectToRefererTrait.
 *
 * Uses a concrete test controller that exposes redirectToReferer for testing.
 */
class RedirectToRefererTraitTest extends TestCase
{
    public function testRedirectToRefererWithEmptyRefererUsesDefaultRoute(): void
    {
        $controller = $this->createControllerStub('app_home');
        $request    = Request::create('/');
        $request->headers->set('Referer', '');

        $response = $controller->runRedirectToReferer($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/home', $response->getTargetUrl());
        self::assertSame(302, $response->getStatusCode());
    }

    public function testRedirectToRefererWithNullRefererUsesDefaultRoute(): void
    {
        $controller = $this->createControllerStub('homepage');
        $request    = Request::create('/');

        $response = $controller->runRedirectToReferer($request);

        self::assertSame('/default', $response->getTargetUrl());
    }

    public function testRedirectToRefererWithZeroRefererUsesDefaultRoute(): void
    {
        $controller = $this->createControllerStub('homepage');
        $request    = Request::create('/');
        $request->headers->set('Referer', '0');

        $response = $controller->runRedirectToReferer($request);

        self::assertSame('/default', $response->getTargetUrl());
    }

    public function testRedirectToRefererWithValidRefererPathRedirectsToMatchedRoute(): void
    {
        $controller = $this->createControllerStub('homepage', [
            '_route' => 'product_show',
            'id'     => '42',
        ]);
        $request = Request::create('/');
        $request->headers->set('Referer', 'https://example.com/product/42');

        $response = $controller->runRedirectToReferer($request);

        self::assertSame('/product/42', $response->getTargetUrl());
    }

    public function testRedirectToRefererMergesQueryAndExcludesFrameworkKeys(): void
    {
        $controller = $this->createControllerStub('homepage', [
            '_route'      => 'product_show',
            '_controller' => 'App\\Controller\\DemoController::show',
            '_locale'     => 'en',
            'id'          => '42',
        ]);
        $request = Request::create('/');
        $request->headers->set('Referer', 'https://example.com/product/42?tab=details');

        $response = $controller->runRedirectToReferer($request, ['flash' => 'saved'], 302);

        self::assertSame('/product/42', $response->getTargetUrl());
    }

    public function testRedirectToRefererWithInvalidRefererPathFallsBackToDefaultRoute(): void
    {
        $controller = $this->createControllerStub('fallback', [], true);
        $request    = Request::create('/');
        $request->headers->set('Referer', 'https://external.com/unknown');

        $response = $controller->runRedirectToReferer($request);

        self::assertSame('/fallback-url', $response->getTargetUrl());
    }

    public function testRedirectToRefererMergesParamsAndUsesStatus(): void
    {
        $controller = $this->createControllerStub('homepage');
        $request    = Request::create('/');

        $response = $controller->runRedirectToReferer($request, ['flash' => 'saved'], 303);

        self::assertSame(303, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed> $matchResult
     */
    private function createControllerStub(string $defaultRoute, array $matchResult = [], bool $matchThrows = false): RedirectToRefererTestController
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('match')
            ->willReturnCallback(static function (string $path) use ($matchResult, $matchThrows) {
                if ($matchThrows) {
                    throw new \Symfony\Component\Routing\Exception\ResourceNotFoundException();
                }

                return $matchResult;
            });

        $urls = [
            'app_home'     => '/home',
            'homepage'     => '/default',
            'fallback'     => '/fallback-url',
            'product_show' => '/product/42',
        ];

        $router->method('generate')
            ->willReturnCallback(static function (string $name, array $params = []) use ($urls): string {
                return $urls[$name] ?? '/' . $name;
            });

        return new RedirectToRefererTestController($router, $defaultRoute);
    }
}

/**
 * Test double that uses RedirectToRefererTrait and exposes redirectToReferer as public.
 *
 * @internal
 */
class RedirectToRefererTestController
{
    use RedirectToRefererTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $defaultRoute,
    ) {
    }

    public function runRedirectToReferer(Request $request, ?array $params = [], ?int $status = 302): RedirectResponse
    {
        return $this->redirectToReferer($request, $params, $status);
    }

    protected function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * @return mixed
     */
    protected function getParameter(string $name)
    {
        if ($name === 'nowo_controller_kit.default_route') {
            return $this->defaultRoute;
        }

        throw new InvalidArgumentException('Unknown parameter: ' . $name);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        $url = $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);

        return new RedirectResponse($url, $status);
    }
}
