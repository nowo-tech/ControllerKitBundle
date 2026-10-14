<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\ControllerKitBundle\Tests\Fixtures\Type\StrictCodeType;

#[ORM\Entity]
#[ORM\Table(name: 'test_item')]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.onlyRead (assigned by Doctrine on flush) */
    private int $id;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Owner::class)]
        #[ORM\JoinColumn(nullable: false)]
        private Owner $owner,
        #[ORM\Column(type: StrictCodeType::NAME, length: 32, unique: true)]
        private string $code,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwner(): Owner
    {
        return $this->owner;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
