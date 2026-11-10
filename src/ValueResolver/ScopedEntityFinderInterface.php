<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\ValueResolver;

use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;

/**
 * Finds the object a `#[MapScopedEntity]` argument asks for, scoped to its owner (DDD P2, 2.4).
 *
 * Optional SPI for objects that the built-in Doctrine query cannot load: read models, objects from a repository that
 * is not Doctrine-backed, an API client. Implementations are services tagged `nowo_controller_kit.scoped_entity_finder`
 * (autoconfigured when the service is; an optional `priority` attribute orders them, highest first). For each argument
 * {@see ScopedEntityValueResolver} asks the finders in order; the first whose {@see self::supports()} returns true
 * decides. When none supports the class, the built-in {@see DoctrineScopedEntityFinder} query runs, as before 2.4.
 * A finder may delegate to {@see DoctrineScopedEntityFinder} itself.
 *
 * WARNING: your finder IS the owner check. Filter by `$ownerId` in the same lookup that loads the object (one query,
 * `WHERE id = :id AND owner = :ownerId`, or the equivalent repository call). Never load by `$id` alone and return the
 * result, never fall back to an unscoped lookup, and return null for an object of another owner: the bundle does not
 * check the owner again, so a finder that skips it is a cross-tenant read (IDOR).
 *
 * The resolver calls {@see self::findScoped()} only with non-empty string or int route values (raw route strings; use
 * route requirements such as `\d+` or `Requirement::UUID` and normalise before comparing). It answers 404 (or `null`
 * for a nullable argument) when the finder returns null, so return null for a missing object, an object of another
 * owner and a malformed id alike. An object that is not an instance of `$class` is a `LogicException`.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface ScopedEntityFinderInterface
{
    /**
     * Service tag of the finders.
     */
    public const TAG = 'nowo_controller_kit.scoped_entity_finder';

    /**
     * Whether this finder loads arguments of class `$class` (with this attribute). Must not throw nor query.
     *
     * @param class-string $class argument type of the controller parameter
     * @param MapScopedEntity $attribute the attribute (or a host subclass)
     */
    public function supports(string $class, MapScopedEntity $attribute): bool;

    /**
     * The object of class `$class` identified by `$id` and owned by `$ownerId`, or null (missing, another owner,
     * malformed id).
     *
     * @param class-string $class argument type of the controller parameter
     * @param int|string $id route value named by `MapScopedEntity::$id`
     * @param int|string $ownerId route value named by `MapScopedEntity::getParentRouteAttribute()`
     * @param MapScopedEntity $attribute the attribute (or a host subclass), for `parent`, `parentField`, `field`, `objectManager`
     */
    public function findScoped(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute): ?object;
}
