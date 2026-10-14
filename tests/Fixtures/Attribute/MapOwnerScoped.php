<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Attribute;

use Attribute;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;

/**
 * Host-style shortcut, like Beacon's `#[MapProjectScoped]`: the owner is preset.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class MapOwnerScoped extends MapScopedEntity
{
    public function __construct(string $id = 'code', string $field = 'code')
    {
        parent::__construct(parent: 'owner', parentId: 'ownerSlug', parentField: 'slug', id: $id, field: $field);
    }
}
