<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityFinderInterface;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers {@see ScopedEntityValueResolver} as a targeted controller value resolver when
 * doctrine/orm is installed and the `doctrine` registry (DoctrineBundle) exists (spec 003), or
 * (2.4+) when the application registers a {@see ScopedEntityFinderInterface} service, which the
 * resolver then delegates to. Without either the bundle boots unchanged and `#[MapScopedEntity]`
 * is not available.
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
     * Registers the targeted value resolver when doctrine/orm and the `doctrine` registry exist, or
     * when a {@see ScopedEntityFinderInterface} service exists.
     *
     * @param ContainerBuilder $container Service container being compiled
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->has(ScopedEntityValueResolver::class)) {
            return;
        }

        $doctrine = interface_exists(EntityManagerInterface::class) && $container->has('doctrine');
        $finder   = $container->has(ScopedEntityFinderInterface::class);
        if (!$doctrine && !$finder) {
            return;
        }

        $arguments = [$doctrine ? new Reference('doctrine') : null];
        if ($finder) {
            $arguments[] = new Reference(ScopedEntityFinderInterface::class);
        }

        $container->register(ScopedEntityValueResolver::class, ScopedEntityValueResolver::class)
            ->setArguments($arguments)
            ->setPublic(false)
            ->addTag('controller.targeted_value_resolver', ['name' => ScopedEntityValueResolver::class]);
    }
}
