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
final class ControllerKitExtension extends Extension
{
    /**
     * Processes the configuration and exposes `nowo_controller_kit.default_route` as a container parameter.
     *
     * @param array<int, array<string, mixed>> $configs Raw configuration blocks
     * @param ContainerBuilder $container Service container being built
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $config        = $processor->processConfiguration($configuration, $configs);

        $container->setParameter('nowo_controller_kit.default_route', $config['default_route']);
        $container->setParameter('nowo_controller_kit.downloads.no_store', $config['downloads']['no_store']);
        $container->setParameter('nowo_controller_kit.downloads.csv_delimiter', $config['downloads']['csv']['delimiter']);
        $container->setParameter('nowo_controller_kit.downloads.csv_bom', $config['downloads']['csv']['bom']);
    }

    /**
     * Returns the configuration alias.
     *
     * @return string `nowo_controller_kit`
     */
    public function getAlias(): string
    {
        return 'nowo_controller_kit';
    }
}
