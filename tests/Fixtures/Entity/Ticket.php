<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Looked up by a `guid` string column (REQ-SEC-006 tests): letter case of the same UUID is the same id. Not in the
 * default fixture schema.
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_ticket')]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.onlyRead (assigned by Doctrine on flush) */
    private int $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 64)]
        private string $ownerSlug,
        #[ORM\Column(type: 'guid')]
        private string $uuid,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwnerSlug(): string
    {
        return $this->ownerSlug;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }
}
