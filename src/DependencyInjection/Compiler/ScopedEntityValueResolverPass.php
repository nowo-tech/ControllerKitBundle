<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\ControllerKitBundle\ValueResolver\DoctrineScopedEntityFinder;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityFinderInterface;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers {@see ScopedEntityValueResolver} as a targeted controller value resolver when
 * doctrine/orm is installed and the `doctrine` registry (DoctrineBundle) exists (spec 003), or
 * (2.4+) when at least one service is tagged `nowo_controller_kit.scoped_entity_finder`
 * ({@see ScopedEntityFinderInterface}, autoconfigured). Tagged finders are passed in priority order.
 * With Doctrine, {@see DoctrineScopedEntityFinder} is also registered so host finders can delegate
 * to it. Without Doctrine and without finders the bundle boots unchanged and `#[MapScopedEntity]`
 * is not available.
 *
 * Added with a positive priority so it runs before FrameworkBundle's ControllerArgumentValueResolverPass
 * (and after autoconfiguration has tagged the finders).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class ScopedEntityValueResolverPass implements CompilerPassInterface
{
    /**
     * Compiler pass priority (runs before FrameworkBundle's ControllerArgumentValueResolverPass).
     */
    public const PRIORITY = 8;

    /**
     * Registers the targeted value resolver when doctrine/orm and the `doctrine` registry exist, or
     * when a finder is tagged.
     *
     * @param ContainerBuilder $container Service container being compiled
     */
    public function process(ContainerBuilder $container): void
    {
        $doctrine = interface_exists(EntityManagerInterface::class) && $container->has('doctrine');
        if ($doctrine && !$container->has(DoctrineScopedEntityFinder::class)) {
            $container->register(DoctrineScopedEntityFinder::class, DoctrineScopedEntityFinder::class)
                ->setArguments([new Reference('doctrine')])
                ->setPublic(false);
        }

        if ($container->has(ScopedEntityValueResolver::class)) {
            return;
        }

        $finders = $container->findTaggedServiceIds(ScopedEntityFinderInterface::TAG) !== [];
        if (!$doctrine && !$finders) {
            return;
        }

        $arguments = [$doctrine ? new Reference('doctrine') : null];
        if ($finders) {
            $arguments[] = new TaggedIteratorArgument(ScopedEntityFinderInterface::TAG);
        }

        $container->register(ScopedEntityValueResolver::class, ScopedEntityValueResolver::class)
            ->setArguments($arguments)
            ->setPublic(false)
            ->addTag('controller.targeted_value_resolver', ['name' => ScopedEntityValueResolver::class]);
    }
}
