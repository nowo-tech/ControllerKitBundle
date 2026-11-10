<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\ControllerKitBundle\Tests\Fixtures\Type\StrictCodeType;

/**
 * Owner referenced by id value (a plain column, no association), as in a DDD model (`Note::$ownerSlug`).
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_note')]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.onlyRead (assigned by Doctrine on flush) */
    private int $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 64)]
        private string $ownerSlug,
        #[ORM\Column(type: StrictCodeType::NAME, length: 32, unique: true)]
        private string $code,
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

    public function getCode(): string
    {
        return $this->code;
    }
}
