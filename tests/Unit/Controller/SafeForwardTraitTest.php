<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Controller;

use BadMethodCallException;
use Nowo\ControllerKitBundle\Controller\SafeForwardTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SafeForwardTraitTest extends TestCase
{
    public function testSafeForwardThrowsWhenMethodDoesNotExist(): void
    {
        $controller = new SafeForwardTestController();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method "missingAction" does not exist in class "');

        $controller->runSafeForward(self::class, 'missingAction');
    }

    public function testSafeForwardCallsForwardWhenMethodExists(): void
    {
        $targetController = self::class;
        $targetMethod     = 'existingMethod';
        $controller       = new SafeForwardTestController($targetController, $targetMethod);

        $response = $controller->runSafeForward($targetController, $targetMethod, ['id' => '1'], ['page' => '2']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getContent());
    }

    public static function existingMethod(): void
    {
    }
}

/**
 * Test double that uses SafeForwardTrait and exposes safeForward as public.
 *
 * @internal
 */
class SafeForwardTestController
{
    use SafeForwardTrait;

    public function __construct(
        private readonly ?string $expectedController = null,
        private readonly ?string $expectedMethod = null,
    ) {
    }

    /**
     * @param array<string, mixed>|null $pathParams
     * @param array<string, mixed>|null $queryParams
     */
    public function runSafeForward(
        string $controllerClass,
        string $methodName,
        ?array $pathParams = [],
        ?array $queryParams = []
    ): Response {
        return $this->safeForward($controllerClass, $methodName, $pathParams, $queryParams);
    }

    /**
     * @param array<string, mixed> $pathParams
     * @param array<string, mixed> $queryParams
     */
    public function forward(string $controller, array $pathParams = [], array $queryParams = []): Response
    {
        if ($this->expectedController !== null
            && $this->expectedMethod !== null
            && $controller === $this->expectedController . '::' . $this->expectedMethod
            && $pathParams === ['id' => '1']
            && $queryParams === ['page' => '2']
        ) {
            return new Response('ok', 200);
        }

        return new Response('unexpected', 400);
    }
}
