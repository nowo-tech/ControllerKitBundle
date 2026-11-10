<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\ReadModel;

use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Nowo\ControllerKitBundle\ValueResolver\DoctrineScopedEntityFinder;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityFinderInterface;

/**
 * Host finder for {@see ItemView}: delegates the owner-scoped lookup to the built-in Doctrine finder, then maps.
 */
final readonly class ItemViewFinder implements ScopedEntityFinderInterface
{
    public function __construct(private DoctrineScopedEntityFinder $doctrine)
    {
    }

    public function supports(string $class, MapScopedEntity $attribute): bool
    {
        return $class === ItemView::class;
    }

    public function findScoped(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute): ?object
    {
        $item = $this->doctrine->findScoped(Item::class, $id, $ownerId, $attribute);

        return $item instanceof Item ? new ItemView($item->getCode(), $item->getOwner()->getSlug()) : null;
    }
}
