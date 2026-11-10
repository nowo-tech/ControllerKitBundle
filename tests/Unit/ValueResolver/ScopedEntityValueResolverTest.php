<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\ValueResolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\Tests\Fixtures\Attribute\MapOwnerScoped;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Note;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Owner;
use Nowo\ControllerKitBundle\Tests\Fixtures\OrmFactory;
use Nowo\ControllerKitBundle\Tests\Fixtures\SqlLogger;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityFinderInterface;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function sprintf;

final class ScopedEntityValueResolverTest extends TestCase
{
    private EntityManagerInterface $manager;

    private SqlLogger $sqlLogger;

    /** @var array{acme: Owner, globex: Owner, acmeItem: Item, globexItem: Item} */
    private array $rows;

    protected function setUp(): void
    {
        $this->sqlLogger = new SqlLogger();
        $this->manager   = OrmFactory::createEntityManager($this->sqlLogger);
        $this->rows      = OrmFactory::seed($this->manager);
    }

    public function testResolvesEntityOfTheRouteOwnerByPrimaryKey(): void
    {
        $item = $this->resolveOne(
            new MapScopedEntity(parent: 'owner', id: 'itemId'),
            ['ownerId' => (string) $this->rows['acme']->getId(), 'itemId' => (string) $this->rows['acmeItem']->getId()],
        );

        self::assertInstanceOf(Item::class, $item);
        self::assertSame('ACME-1', $item->getCode());
    }

    public function testResolvesIntegerRouteValues(): void
    {
        $item = $this->resolveOne(
            new MapScopedEntity(parent: 'owner', id: 'itemId'),
            ['ownerId' => $this->rows['acme']->getId(), 'itemId' => $this->rows['acmeItem']->getId()],
        );

        self::assertInstanceOf(Item::class, $item);
    }

    public function testResolvesThroughAHostSubclassByNonPrimaryFields(): void
    {
        $item = $this->resolveOne(new MapOwnerScoped(), ['ownerSlug' => 'globex', 'code' => 'GLOBEX-1']);

        self::assertInstanceOf(Item::class, $item);
        self::assertSame('globex', $item->getOwner()->getSlug());
    }

    public function testEntityOfAnotherOwnerIs404(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(sprintf('"%s" object not found by "%s".', Item::class, ScopedEntityValueResolver::class));

        $this->resolveOne(new MapOwnerScoped(), ['ownerSlug' => 'acme', 'code' => 'GLOBEX-1']);
    }

    public function testUnknownEntityIs404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->resolveOne(new MapOwnerScoped(), ['ownerSlug' => 'acme', 'code' => 'ACME-999']);
    }

    public function testMalformedIdentifierForTheColumnTypeIs404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->resolveOne(new MapOwnerScoped(), ['ownerSlug' => 'acme', 'code' => 'not a code']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function missingRouteValues(): iterable
    {
        yield 'no child id' => [['ownerSlug' => 'acme']];
        yield 'no owner id' => [['code' => 'ACME-1']];
        yield 'empty child id' => [['ownerSlug' => 'acme', 'code' => '']];
        yield 'non-scalar owner id' => [['ownerSlug' => ['acme'], 'code' => 'ACME-1']];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    #[DataProvider('missingRouteValues')]
    public function testMissingRouteValuesAre404(array $attributes): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->resolveOne(new MapOwnerScoped(), $attributes);
    }

    public function testNullableArgumentResolvesToNullWhenNotFound(): void
    {
        $values = $this->resolve(new MapOwnerScoped(), ['ownerSlug' => 'acme', 'code' => 'GLOBEX-1'], nullable: true);

        self::assertSame([null], $values);
    }

    public function testNullableArgumentResolvesToNullWhenRouteValueIsMissing(): void
    {
        $values = $this->resolve(new MapOwnerScoped(), ['ownerSlug' => 'acme'], nullable: true);

        self::assertSame([null], $values);
    }

    public function testArgumentWithoutTheAttributeIsIgnored(): void
    {
        $resolver = new ScopedEntityValueResolver($this->registry());
        $argument = new ArgumentMetadata('item', Item::class, false, false, null);

        self::assertSame([], [...$resolver->resolve(new Request(), $argument)]);
    }

    public function testNamedObjectManagerIsUsed(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::never())->method('getManagerForClass');
        $registry->expects(self::once())->method('getManager')->with('tenant')->willReturn($this->manager);

        $resolver = new ScopedEntityValueResolver($registry);
        $argument = new ArgumentMetadata('item', Item::class, false, false, null, false, [new MapScopedEntity(parent: 'owner', id: 'itemId', objectManager: 'tenant')]);
        $request  = new Request([], [], ['ownerId' => (string) $this->rows['acme']->getId(), 'itemId' => (string) $this->rows['acmeItem']->getId()]);

        self::assertInstanceOf(Item::class, [...$resolver->resolve($request, $argument)][0]);
    }

    public function testUntypedArgumentIsALogicError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('#[MapScopedEntity] needs an entity-typed argument ("$item").');

        $this->resolve(new MapOwnerScoped(), [], type: null);
    }

    public function testNonClassTypeIsALogicError(): void
    {
        $this->expectException(LogicException::class);

        $this->resolve(new MapOwnerScoped(), [], type: 'string');
    }

    public function testClassWithoutAnOrmManagerIsALogicError(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->createMock(ObjectManager::class));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('#[MapScopedEntity] needs a Doctrine ORM entity manager for "%s" ("$item").', Item::class));

        $this->resolve(new MapOwnerScoped(), [], registry: $registry);
    }

    public function testUnknownParentAssociationIsALogicError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('#[MapScopedEntity] "%s" has no association "project" ("$item").', Item::class));

        $this->resolve(new MapScopedEntity(parent: 'project'), []);
    }

    public function testUnknownFieldIsALogicError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('#[MapScopedEntity] "%s" has no field "uuid" ("$item").', Item::class));

        $this->resolve(new MapScopedEntity(parent: 'owner', field: 'uuid'), []);
    }

    public function testUnknownParentFieldIsALogicError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('#[MapScopedEntity] "%s" has no field "uuid" ("$item").', Owner::class));

        $this->resolve(new MapScopedEntity(parent: 'owner', parentField: 'uuid'), []);
    }

    public function testResolvesWithASingleQueryJoinedOnTheOwner(): void
    {
        $this->sqlLogger->reset();

        $item = $this->resolveOne(new MapOwnerScoped(), ['ownerSlug' => 'acme', 'code' => 'ACME-1']);

        self::assertInstanceOf(Item::class, $item);
        self::assertCount(1, $this->sqlLogger->sql);
        self::assertStringContainsString('JOIN test_owner', $this->sqlLogger->sql[0]);
    }

    public function testResolvesAnOwnerReferencedByAPlainField(): void
    {
        OrmFactory::seedNotes($this->manager);
        $this->sqlLogger->reset();

        $note = $this->resolveOne(
            new MapScopedEntity(parent: 'ownerSlug', id: 'code', field: 'code'),
            ['ownerSlugId' => 'globex', 'code' => 'NOTE-2'],
            Note::class,
        );

        self::assertInstanceOf(Note::class, $note);
        self::assertSame('globex', $note->getOwnerSlug());
        self::assertCount(1, $this->sqlLogger->sql);
        self::assertStringNotContainsString('JOIN', $this->sqlLogger->sql[0]);
    }

    public function testPlainFieldOwnerOfAnotherOwnerIs404(): void
    {
        OrmFactory::seedNotes($this->manager);

        $this->expectException(NotFoundHttpException::class);

        $this->resolveOne(
            new MapScopedEntity(parent: 'ownerSlug', parentId: 'owner', id: 'code', field: 'code'),
            ['owner' => 'acme', 'code' => 'NOTE-2'],
            Note::class,
        );
    }

    public function testPlainFieldOwnerWithAMalformedIdentifierIs404(): void
    {
        OrmFactory::seedNotes($this->manager);

        $values = $this->resolve(
            new MapScopedEntity(parent: 'ownerSlug', parentId: 'owner', id: 'code', field: 'code'),
            ['owner' => 'acme', 'code' => 'not a code'],
            nullable: true,
            type: Note::class,
        );

        self::assertSame([null], $values);
    }

    public function testFinderReplacesTheDoctrineQuery(): void
    {
        $found  = new stdClass();
        $finder = $this->createMock(ScopedEntityFinderInterface::class);
        $finder->expects(self::once())
            ->method('findScoped')
            ->with(Item::class, 'ACME-1', 'acme', self::isInstanceOf(MapOwnerScoped::class))
            ->willReturn($found);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::never())->method('getManagerForClass');

        $resolver = new ScopedEntityValueResolver($registry, $finder);
        $argument = new ArgumentMetadata('item', Item::class, false, false, null, false, [new MapOwnerScoped()]);

        self::assertSame([$found], [...$resolver->resolve(new Request([], [], ['ownerSlug' => 'acme', 'code' => 'ACME-1']), $argument)]);
    }

    public function testFinderWithoutDoctrine(): void
    {
        $finder = new class implements ScopedEntityFinderInterface {
            public function findScoped(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute): ?object
            {
                return $ownerId === 'acme' && $id === 7 ? new stdClass() : null;
            }
        };
        $resolver = new ScopedEntityValueResolver(null, $finder);
        $argument = new ArgumentMetadata('item', Item::class, false, false, null, true, [new MapOwnerScoped()]);

        self::assertInstanceOf(stdClass::class, [...$resolver->resolve(new Request([], [], ['ownerSlug' => 'acme', 'code' => 7]), $argument)][0]);
        self::assertSame([null], [...$resolver->resolve(new Request([], [], ['ownerSlug' => 'globex', 'code' => 7]), $argument)]);
    }

    public function testFinderNullIs404(): void
    {
        $finder = $this->createMock(ScopedEntityFinderInterface::class);
        $finder->method('findScoped')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(sprintf('"%s" object not found by "%s".', Item::class, ScopedEntityValueResolver::class));

        $resolver = new ScopedEntityValueResolver(null, $finder);
        $argument = new ArgumentMetadata('item', Item::class, false, false, null, false, [new MapOwnerScoped()]);
        [...$resolver->resolve(new Request([], [], ['ownerSlug' => 'acme', 'code' => 'ACME-1']), $argument)];
    }

    public function testFinderIsNotCalledWithMissingRouteValues(): void
    {
        $finder = $this->createMock(ScopedEntityFinderInterface::class);
        $finder->expects(self::never())->method('findScoped');

        $resolver = new ScopedEntityValueResolver(null, $finder);
        $argument = new ArgumentMetadata('item', Item::class, false, false, null, true, [new MapOwnerScoped()]);

        self::assertSame([null], [...$resolver->resolve(new Request([], [], ['ownerSlug' => 'acme', 'code' => '']), $argument)]);
    }

    public function testNoDoctrineAndNoFinderIsALogicError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('#[MapScopedEntity] needs Doctrine ORM or a %s service ("$item").', ScopedEntityFinderInterface::class));

        $resolver = new ScopedEntityValueResolver(null);
        $argument = new ArgumentMetadata('item', Item::class, false, false, null, false, [new MapOwnerScoped()]);
        [...$resolver->resolve(new Request(), $argument)];
    }

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->manager);

        return $registry;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function resolveOne(MapScopedEntity $attribute, array $attributes, string $type = Item::class): ?object
    {
        return $this->resolve($attribute, $attributes, type: $type)[0] ?? null;
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return list<object|null>
     */
    private function resolve(MapScopedEntity $attribute, array $attributes, bool $nullable = false, ?string $type = Item::class, ?ManagerRegistry $registry = null): array
    {
        $resolver = new ScopedEntityValueResolver($registry ?? $this->registry());
        $argument = new ArgumentMetadata('item', $type, false, false, null, $nullable, [$attribute]);

        return array_values([...$resolver->resolve(new Request([], [], $attributes), $argument)]);
    }
}
