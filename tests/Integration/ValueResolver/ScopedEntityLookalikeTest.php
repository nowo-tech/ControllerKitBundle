<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Integration\ValueResolver;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\Tests\Fixtures\Attribute\MapOwnerScoped;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Board;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Note;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Owner;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Ticket;
use Nowo\ControllerKitBundle\Tests\Fixtures\LookalikeCollation;
use Nowo\ControllerKitBundle\Tests\Fixtures\OrmFactory;
use Nowo\ControllerKitBundle\ValueResolver\DoctrineScopedEntityFinder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function strtoupper;

/**
 * REQ-SEC-006 (look-alike ids): the host columns get an SQLite collation that folds like the default MySQL / MariaDB
 * ones ({@see LookalikeCollation}), so SQL alone matches `Acme`, `acmé`, `acme ` ... for `acme`. The finder must only
 * serve the row whose id and owner equal the route values byte for byte.
 */
final class ScopedEntityLookalikeTest extends TestCase
{
    private EntityManagerInterface $manager;

    private Connection $connection;

    private DoctrineScopedEntityFinder $finder;

    protected function setUp(): void
    {
        $this->manager    = OrmFactory::createEntityManager();
        $this->connection = $this->manager->getConnection();
        (new SchemaTool($this->manager))->createSchema([$this->manager->getClassMetadata(Ticket::class), $this->manager->getClassMetadata(Board::class)]);
        LookalikeCollation::register($this->connection);
        LookalikeCollation::recreate($this->connection, 'test_owner', ['slug']);
        LookalikeCollation::recreate($this->connection, 'test_item', ['code']);
        LookalikeCollation::recreate($this->connection, 'test_note', ['ownerSlug', 'code']);
        LookalikeCollation::recreate($this->connection, 'test_ticket', ['ownerSlug', 'uuid']);
        LookalikeCollation::recreate($this->connection, 'test_board', ['code']);
        OrmFactory::seed($this->manager);   // owners acme / globex, items ACME-1 / GLOBEX-1
        OrmFactory::seedNotes($this->manager); // NOTE-1 of acme, NOTE-2 of globex

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->manager);
        $this->finder = new DoctrineScopedEntityFinder($registry);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function lookalikeOwners(): iterable
    {
        yield 'upper case' => ['Acme'];
        yield 'accent' => ["acm\u{00E9}"];
        yield 'trailing space' => ['acme '];
        yield 'trailing newline' => ["acme\n"];
        yield 'NBSP' => ["acme\u{00A0}"];
        yield 'ZWSP' => ["ac\u{200B}me"];
    }

    #[DataProvider('lookalikeOwners')]
    public function testALookalikeOwnerRouteValueIsNotServedTheOwnersRow(string $owner): void
    {
        // Guard: SQL alone matches the look-alike.
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM test_owner WHERE slug = ?', [$owner]));
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM test_note WHERE ownerSlug = ?', [$owner]));

        self::assertNull($this->finder->findScoped(Item::class, 'ACME-1', $owner, new MapOwnerScoped()));
        self::assertNull($this->finder->findScoped(Note::class, 'NOTE-1', $owner, $this->noteByOwnerSlug()));
        self::assertNull($this->finder->findForRoute(Note::class, 'NOTE-1', $owner, $this->noteByOwnerSlug(), 'note'));

        // The exact owner is still served.
        self::assertInstanceOf(Item::class, $this->finder->findScoped(Item::class, 'ACME-1', 'acme', new MapOwnerScoped()));
        self::assertInstanceOf(Note::class, $this->finder->findScoped(Note::class, 'NOTE-1', 'acme', $this->noteByOwnerSlug()));
    }

    public function testALookalikeOwnerRowDoesNotHideOrReplaceTheExactOne(): void
    {
        // Look-alike tenants of a host whose owner column is not unique (or folds differently).
        $this->connection->insert('test_note', ['ownerSlug' => 'Acme', 'code' => 'NOTE-1']);
        $this->connection->insert('test_note', ['ownerSlug' => 'acme ', 'code' => 'NOTE-1']);

        $note = $this->finder->findScoped(Note::class, 'NOTE-1', 'Acme', $this->noteByOwnerSlug());
        self::assertInstanceOf(Note::class, $note);
        self::assertSame('Acme', $note->getOwnerSlug());

        $note = $this->finder->findScoped(Note::class, 'NOTE-1', 'acme', $this->noteByOwnerSlug());
        self::assertInstanceOf(Note::class, $note);
        self::assertSame('acme', $note->getOwnerSlug());
    }

    public function testALookalikeIdIsNotServedAnotherRow(): void
    {
        // StrictCodeType accepts a trailing newline (`$` without /D): a look-alike id that SQL folds onto ACME-1.
        self::assertNull($this->finder->findScoped(Item::class, "ACME-1\n", 'acme', new MapOwnerScoped()));
    }

    public function testTwoExactRowsStillFailAsBefore(): void
    {
        $this->connection->insert('test_note', ['ownerSlug' => 'acme', 'code' => 'NOTE-1']);

        $this->expectException(NonUniqueResultException::class);
        $this->finder->findScoped(Note::class, 'NOTE-1', 'acme', $this->noteByOwnerSlug());
    }

    public function testAGuidColumnAcceptsTheOtherLetterCaseOnly(): void
    {
        $uuid = '0192f1a4-7d3e-7c4b-9a1e-1b2c3d4e5f60';
        $this->manager->persist(new Ticket('acme', $uuid));
        $this->manager->flush();
        $this->manager->clear();
        $field = new MapScopedEntity(parent: 'ownerSlug', parentId: 'owner', id: 'uuid', field: 'uuid');

        self::assertInstanceOf(Ticket::class, $this->finder->findScoped(Ticket::class, $uuid, 'acme', $field));
        self::assertInstanceOf(Ticket::class, $this->finder->findScoped(Ticket::class, strtoupper($uuid), 'acme', $field));
        self::assertNull($this->finder->findScoped(Ticket::class, $uuid . ' ', 'acme', $field));
        self::assertNull($this->finder->findScoped(Ticket::class, $uuid . "\u{200B}", 'acme', $field));
        self::assertNull($this->finder->findScoped(Ticket::class, $uuid, 'Acme', $field));
    }

    public function testACollectionOfOwnersIsCheckedOwnerByOwner(): void
    {
        $acme   = $this->manager->getRepository(Owner::class)->findOneBy(['slug' => 'acme']);
        $globex = $this->manager->getRepository(Owner::class)->findOneBy(['slug' => 'globex']);
        self::assertInstanceOf(Owner::class, $acme);
        self::assertInstanceOf(Owner::class, $globex);
        $this->manager->persist(new Board([$acme, $globex], 'BOARD-1'));
        $this->manager->flush();
        $this->manager->clear();
        $shared = new MapScopedEntity(parent: 'owners', parentId: 'owner', parentField: 'slug', id: 'code', field: 'code');

        $board = $this->finder->findScoped(Board::class, 'BOARD-1', 'globex', $shared);
        self::assertInstanceOf(Board::class, $board);
        self::assertCount(2, $board->getOwners(), 'the owner collection is not hydrated partially');
        self::assertInstanceOf(Board::class, $this->finder->findScoped(Board::class, 'BOARD-1', 'acme', $shared));
        self::assertNull($this->finder->findScoped(Board::class, 'BOARD-1', 'Acme', $shared));
        self::assertNull($this->finder->findScoped(Board::class, 'board-1', 'acme', $shared));
    }

    private function noteByOwnerSlug(): MapScopedEntity
    {
        return new MapScopedEntity(parent: 'ownerSlug', parentId: 'owner', id: 'code', field: 'code');
    }
}
