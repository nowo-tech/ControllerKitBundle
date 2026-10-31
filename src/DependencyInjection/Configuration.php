<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function in_array;
use function is_string;
use function strlen;

/**
 * Defines the configuration tree for Controller Kit Bundle (nowo_controller_kit).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * Builds the `nowo_controller_kit` tree (`default_route`, non-empty, default `homepage`; `downloads` defaults).
     *
     * @return TreeBuilder Configuration tree
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nowo_controller_kit');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('default_route')
                    ->info('Route name used when redirectToReferer has no valid Referer header (e.g. homepage, app_home).')
                    ->defaultValue('homepage')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('downloads')
                    ->info('Defaults of DownloadResponseTrait::csvDownload() / jsonDownload() (each call may override them).')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('no_store')
                            ->info('Send Cache-Control: no-store, private on downloads (exports with secrets or personal data).')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('csv')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('delimiter')
                                    ->info('One single-byte field delimiter (",", ";" or a tab); not a double quote nor a line break.')
                                    ->defaultValue(',')
                                    ->validate()
                                        ->ifTrue(static fn (mixed $v): bool => !is_string($v) || strlen($v) !== 1 || in_array($v, ['"', "\n", "\r"], true))
                                        ->thenInvalid('The CSV delimiter must be one byte other than a double quote or a line break, %s given.')
                                    ->end()
                                ->end()
                                ->booleanNode('bom')
                                    ->info('Write the UTF-8 byte order mark first (Excel then reads the file as UTF-8).')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
