<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Dependency injection extension for Controller Kit Bundle.
 * Loads configuration and sets the default_route parameter for redirectToReferer.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class ControllerKitExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $container->setParameter('nowo_controller_kit.default_route', $config['default_route']);
    }

    public function getAlias(): string
    {
        return 'nowo_controller_kit';
    }
}
