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
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Owner;
use Nowo\ControllerKitBundle\Tests\Fixtures\OrmFactory;
use Nowo\ControllerKitBundle\Tests\Fixtures\SqlLogger;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->manager);

        return $registry;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function resolveOne(MapScopedEntity $attribute, array $attributes): ?object
    {
        return $this->resolve($attribute, $attributes)[0] ?? null;
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
