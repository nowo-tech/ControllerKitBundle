<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Controller;

use BadMethodCallException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait providing safe forward functionality for controllers.
 *
 * Forwards the request to another controller method after validating that the
 * target method exists, preventing runtime errors from missing methods.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait SafeForwardTrait
{
    /**
     * Safely forwards the request to a controller method after validating its existence.
     *
     * @param string   $controllerClass Fully qualified class name of the target controller
     * @param string   $methodName     Name of the method to forward to
     * @param array|null $pathParams   Path/request parameters for the forwarded method
     * @param array|null $queryParams  Query parameters for the forwarded method
     *
     * @return JsonResponse|Response Response from the forwarded controller method
     *
     * @throws BadMethodCallException if the method does not exist in the target class
     */
    protected function safeForward(
        string $controllerClass,
        string $methodName,
        ?array $pathParams = [],
        ?array $queryParams = []
    ): JsonResponse|Response {
        if (!method_exists($controllerClass, $methodName)) {
            throw new BadMethodCallException(sprintf(
                'Method "%s" does not exist in class "%s".',
                $methodName,
                $controllerClass
            ));
        }

        $forwardTarget = $controllerClass . '::' . $methodName;

        return $this->forward($forwardTarget, $pathParams ?? [], $queryParams ?? []);
    }
}
