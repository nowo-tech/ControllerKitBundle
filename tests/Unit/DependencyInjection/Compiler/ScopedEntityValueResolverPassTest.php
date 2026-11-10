<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\DependencyInjection\Compiler;

use Nowo\ControllerKitBundle\DependencyInjection\Compiler\ScopedEntityValueResolverPass;
use Nowo\ControllerKitBundle\ValueResolver\DoctrineScopedEntityFinder;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityFinderInterface;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ScopedEntityValueResolverPassTest extends TestCase
{
    public function testRegistersTheTargetedResolverWhenDoctrineIsAvailable(): void
    {
        $container = new ContainerBuilder();
        $container->register('doctrine', stdClass::class);

        (new ScopedEntityValueResolverPass())->process($container);

        self::assertTrue($container->hasDefinition(ScopedEntityValueResolver::class));
        $definition = $container->getDefinition(ScopedEntityValueResolver::class);
        self::assertSame(ScopedEntityValueResolver::class, $definition->getClass());
        self::assertFalse($definition->isPublic());
        self::assertEquals([new Reference('doctrine')], $definition->getArguments());
        self::assertSame([['name' => ScopedEntityValueResolver::class]], $definition->getTag('controller.targeted_value_resolver'));
    }

    public function testDoesNothingWithoutTheDoctrineRegistry(): void
    {
        $container = new ContainerBuilder();

        (new ScopedEntityValueResolverPass())->process($container);

        self::assertFalse($container->hasDefinition(ScopedEntityValueResolver::class));
    }

    public function testPassesTaggedFindersWithDoctrine(): void
    {
        $container = new ContainerBuilder();
        $container->register('doctrine', stdClass::class);
        $container->register('app.finder', stdClass::class)->addTag(ScopedEntityFinderInterface::TAG);

        (new ScopedEntityValueResolverPass())->process($container);

        self::assertEquals(
            [new Reference('doctrine'), new TaggedIteratorArgument(ScopedEntityFinderInterface::TAG)],
            $container->getDefinition(ScopedEntityValueResolver::class)->getArguments(),
        );
    }

    public function testRegistersTheResolverWithATaggedFinderAndNoDoctrine(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.finder', stdClass::class)->addTag(ScopedEntityFinderInterface::TAG);

        (new ScopedEntityValueResolverPass())->process($container);

        $definition = $container->getDefinition(ScopedEntityValueResolver::class);
        self::assertEquals([null, new TaggedIteratorArgument(ScopedEntityFinderInterface::TAG)], $definition->getArguments());
        self::assertSame([['name' => ScopedEntityValueResolver::class]], $definition->getTag('controller.targeted_value_resolver'));
        self::assertFalse($container->hasDefinition(DoctrineScopedEntityFinder::class));
    }

    public function testRegistersTheDoctrineFinderForDelegation(): void
    {
        $container = new ContainerBuilder();
        $container->register('doctrine', stdClass::class);

        (new ScopedEntityValueResolverPass())->process($container);

        $definition = $container->getDefinition(DoctrineScopedEntityFinder::class);
        self::assertEquals([new Reference('doctrine')], $definition->getArguments());
        self::assertFalse($definition->hasTag(ScopedEntityFinderInterface::TAG));
    }

    public function testKeepsAnApplicationDoctrineFinder(): void
    {
        $container = new ContainerBuilder();
        $container->register('doctrine', stdClass::class);
        $container->register(DoctrineScopedEntityFinder::class, stdClass::class);

        (new ScopedEntityValueResolverPass())->process($container);

        self::assertSame(stdClass::class, $container->getDefinition(DoctrineScopedEntityFinder::class)->getClass());
    }

    public function testKeepsAnApplicationOverride(): void
    {
        $container = new ContainerBuilder();
        $container->register('doctrine', stdClass::class);
        $container->register(ScopedEntityValueResolver::class, stdClass::class);

        (new ScopedEntityValueResolverPass())->process($container);

        self::assertSame(stdClass::class, $container->getDefinition(ScopedEntityValueResolver::class)->getClass());
    }
}
