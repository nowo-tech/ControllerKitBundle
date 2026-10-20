<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers {@see ScopedEntityValueResolver} as a targeted controller value resolver when
 * doctrine/orm is installed and the `doctrine` registry (DoctrineBundle) exists (spec 003).
 * Without them the bundle boots unchanged and `#[MapScopedEntity]` is not available.
 *
 * Added with a positive priority so it runs before FrameworkBundle's ControllerArgumentValueResolverPass.
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
     * Registers the targeted value resolver when doctrine/orm and the `doctrine` registry exist.
     *
     * @param ContainerBuilder $container Service container being compiled
     */
    public function process(ContainerBuilder $container): void
    {
        if (!interface_exists(EntityManagerInterface::class) || !$container->has('doctrine') || $container->has(ScopedEntityValueResolver::class)) {
            return;
        }

        $container->register(ScopedEntityValueResolver::class, ScopedEntityValueResolver::class)
            ->setArguments([new Reference('doctrine')])
            ->setPublic(false)
            ->addTag('controller.targeted_value_resolver', ['name' => ScopedEntityValueResolver::class]);
    }
}
