<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Attribute;

use Attribute;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;

/**
 * Loads a Doctrine entity from the route and answers 404 unless it belongs to the owner named by
 * another route attribute (spec 003). One query joined on the owner association: a missing entity
 * and an entity of another owner are the same 404 (no existence leak, no manual owner check).
 *
 *     #[Route('/projects/{projectId}/issues/{issueId}')]
 *     public function show(
 *         #[MapScopedEntity(parent: 'project', parentId: 'projectId', parentField: 'uuid', id: 'issueId', field: 'uuid')]
 *         Issue $issue,
 *     ): Response
 *
 * Not final: an application may extend it to preset its own owner (e.g. `#[MapProjectScoped]`).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class MapScopedEntity extends ValueResolver
{
    /**
     * @param string $parent association on the entity that points to the owner (e.g. `project`)
     * @param string|null $parentId route attribute holding the owner identifier (default: `{$parent}Id`)
     * @param string $parentField owner field matched against the `$parentId` route value (e.g. `id`, `uuid`, `slug`)
     * @param string $id route attribute holding the entity identifier
     * @param string $field entity field matched against the `$id` route value
     * @param string|null $objectManager Doctrine entity manager name (default: the manager of the entity class)
     */
    public function __construct(
        public readonly string $parent,
        public readonly ?string $parentId = null,
        public readonly string $parentField = 'id',
        public readonly string $id = 'id',
        public readonly string $field = 'id',
        public readonly ?string $objectManager = null,
    ) {
        parent::__construct(ScopedEntityValueResolver::class);
    }

    /**
     * Route attribute holding the owner identifier.
     */
    public function getParentRouteAttribute(): string
    {
        return $this->parentId ?? $this->parent . 'Id';
    }
}
