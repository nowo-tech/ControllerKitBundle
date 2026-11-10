<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Controller;

use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\Tests\Fixtures\Attribute\MapOwnerScoped;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Note;
use Nowo\ControllerKitBundle\Tests\Fixtures\ReadModel\ItemView;
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

    /**
     * Read model loaded by a tagged host finder (2.4+).
     */
    public function view(#[MapOwnerScoped] ItemView $view): Response
    {
        return new Response($view->ownerSlug . '/' . $view->code);
    }

    /**
     * Owner referenced by a plain field (2.4+): `Note::$ownerSlug` holds the owner id value.
     */
    public function note(
        #[MapScopedEntity(parent: 'ownerSlug', parentId: 'ownerSlug', id: 'code', field: 'code')]
        Note $note,
    ): Response {
        return new Response($note->getCode());
    }
}
