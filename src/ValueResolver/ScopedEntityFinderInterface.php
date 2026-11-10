<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\ValueResolver;

use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;

/**
 * Finds the object a `#[MapScopedEntity]` argument asks for, scoped to its owner (DDD P2, 2.4).
 *
 * Optional SPI: register a service under this interface and {@see ScopedEntityValueResolver} delegates every
 * `#[MapScopedEntity]` argument to it instead of running its Doctrine ORM query. Use it when the owner is not a
 * Doctrine association or field you can map, or when the object comes from a repository that is not Doctrine-backed
 * (an application query, a read model, an API client). Without Doctrine installed, the resolver is registered as soon
 * as this service exists.
 *
 * The resolver calls it only with non-empty string or int route values, and answers 404 (or `null` for a nullable
 * argument) when it returns null. Return null for a missing object, an object of another owner and a malformed id
 * alike, so the answer never tells them apart.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface ScopedEntityFinderInterface
{
    /**
     * The object of class `$class` identified by `$id` and owned by `$ownerId`, or null.
     *
     * @param class-string $class argument type of the controller parameter
     * @param int|string $id route value named by `MapScopedEntity::$id`
     * @param int|string $ownerId route value named by `MapScopedEntity::getParentRouteAttribute()`
     * @param MapScopedEntity $attribute the attribute (or a host subclass), for `parent`, `parentField`, `field`, `objectManager`
     */
    public function findScoped(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute): ?object;
}
