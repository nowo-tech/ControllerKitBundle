<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Shared by several owners (collection-valued `parent`, REQ-SEC-006 tests). Not in the default fixture schema.
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_board')]
class Board
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.onlyRead (assigned by Doctrine on flush) */
    private int $id;

    /** @var Collection<int, Owner> */
    #[ORM\ManyToMany(targetEntity: Owner::class)]
    #[ORM\JoinTable(name: 'test_board_owner')]
    private Collection $owners;

    /**
     * @param list<Owner> $owners
     */
    public function __construct(
        array $owners,
        #[ORM\Column(type: 'string', length: 32)]
        private string $code,
    ) {
        $this->owners = new ArrayCollection($owners);
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Owner>
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
