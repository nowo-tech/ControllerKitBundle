<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Controller;

use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\Tests\Fixtures\Attribute\MapOwnerScoped;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Symfony\Component\HttpFoundation\Response;

final class ItemController
{
    public function showById(
        #[MapScopedEntity(parent: 'owner', parentId: 'ownerId', id: 'itemId')]
        Item $item,
    ): Response {
        return new Response($item->getCode());
    }

    public function showByCode(#[MapOwnerScoped] Item $item): Response
    {
        return new Response($item->getCode());
    }

    public function optional(
        #[MapScopedEntity(parent: 'owner', parentId: 'ownerSlug', parentField: 'slug', id: 'code', field: 'code')]
        ?Item $item,
    ): Response {
        return new Response($item?->getCode() ?? 'none');
    }
}
