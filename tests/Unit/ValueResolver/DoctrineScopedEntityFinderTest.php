<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\ValueResolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use LogicException;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\Tests\Fixtures\Attribute\MapOwnerScoped;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Note;
use Nowo\ControllerKitBundle\Tests\Fixtures\OrmFactory;
use Nowo\ControllerKitBundle\Tests\Fixtures\SqlLogger;
use Nowo\ControllerKitBundle\ValueResolver\DoctrineScopedEntityFinder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function class_exists;
use function sprintf;

/**
 * The built-in lookup as a host finder would call it (2.4+).
 */
final class DoctrineScopedEntityFinderTest extends TestCase
{
    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        $this->manager = OrmFactory::createEntityManager();
        OrmFactory::seed($this->manager);
        OrmFactory::seedNotes($this->manager);
    }

    public function testSupportsDoctrineEntitiesOnly(): void
    {
        $finder = new DoctrineScopedEntityFinder($this->registry());

        self::assertTrue($finder->supports(Item::class, new MapOwnerScoped()));
        self::assertFalse($finder->supports(stdClass::class, new MapOwnerScoped()));
        self::assertFalse($finder->supports('App\\Missing\\Thing', new MapOwnerScoped())); // @phpstan-ignore argument.type
    }

    public function testDoesNotSupportAClassWithoutAnOrmManager(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->createMock(ObjectManager::class));

        self::assertFalse((new DoctrineScopedEntityFinder($registry))->supports(Item::class, new MapOwnerScoped()));
    }

    public function testDoesNotSupportAnUnknownNamedManager(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManager')->willThrowException(new InvalidArgumentException('no manager'));

        self::assertFalse((new DoctrineScopedEntityFinder($registry))->supports(Item::class, new MapScopedEntity(parent: 'owner', objectManager: 'nope')));
    }

    public function testFindsScopedForAHostFinder(): void
    {
        $finder = new DoctrineScopedEntityFinder($this->registry());

        $item = $finder->findScoped(Item::class, 'ACME-1', 'acme', new MapOwnerScoped());
        self::assertInstanceOf(Item::class, $item);
        self::assertNull($finder->findScoped(Item::class, 'GLOBEX-1', 'acme', new MapOwnerScoped()));
        self::assertNull($finder->findScoped(Item::class, 'malformed', 'acme', new MapOwnerScoped()));

        $field = new MapScopedEntity(parent: 'ownerSlug', parentId: 'owner', id: 'code', field: 'code');
        self::assertInstanceOf(Note::class, $finder->findScoped(Note::class, 'NOTE-1', 'acme', $field));
        self::assertNull($finder->findScoped(Note::class, 'NOTE-1', 'globex', $field));
    }

    /**
     * @return iterable<string, array{int|string, int|string}>
     */
    public static function emptyIds(): iterable
    {
        yield 'empty owner' => ['NOTE-9', ''];
        yield 'empty id' => ['', 'acme'];
        yield 'both empty' => ['', ''];
    }

    /**
     * Audit KM-C-L2: a direct call with an empty owner id must not match a row whose owner column is ''.
     */
    #[DataProvider('emptyIds')]
    public function testEmptyIdsMatchNothingWithoutAQuery(int|string $id, int|string $ownerId): void
    {
        $logger  = new SqlLogger();
        $manager = OrmFactory::createEntityManager($logger);
        $manager->persist(new Note('', 'NOTE-9'));
        $manager->flush();
        $manager->clear();
        $logger->reset();

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $field = new MapScopedEntity(parent: 'ownerSlug', parentId: 'owner', id: 'code', field: 'code');
        self::assertNull((new DoctrineScopedEntityFinder($registry))->findScoped(Note::class, $id, $ownerId, $field));
        self::assertSame([], $logger->sql);
    }

    public function testMappingErrorsHaveNoArgumentSuffixWhenCalledDirectly(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('#[MapScopedEntity] "%s" has no association "project".', Item::class));

        (new DoctrineScopedEntityFinder($this->registry()))->findScoped(Item::class, 1, 1, new MapScopedEntity(parent: 'project'));
    }

    public function testFindForRouteReportsTheArgumentAndChecksTheMappingFirst(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('#[MapScopedEntity] "%s" has no field "uuid" ("$item").', Item::class));

        (new DoctrineScopedEntityFinder($this->registry()))->findForRoute(Item::class, null, null, new MapScopedEntity(parent: 'owner', field: 'uuid'), 'item');
    }

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturnCallback(
            fn (string $class): ?EntityManagerInterface => class_exists($class) && !$this->manager->getMetadataFactory()->isTransient($class) ? $this->manager : null,
        );

        return $registry;
    }
}
