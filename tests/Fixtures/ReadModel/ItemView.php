<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\ReadModel;

/**
 * Read model, not a Doctrine entity.
 */
final readonly class ItemView
{
    public function __construct(
        public string $code,
        public string $ownerSlug,
    ) {
    }
}
