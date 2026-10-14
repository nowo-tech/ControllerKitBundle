<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Integration\ValueResolver;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Nowo\ControllerKitBundle\NowoControllerKitBundle;
use Nowo\ControllerKitBundle\Tests\Fixtures\Controller\ItemController;
use Nowo\ControllerKitBundle\Tests\Fixtures\Type\StrictCodeType;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use function dirname;

use const PHP_VERSION_ID;

/**
 * Minimal app: FrameworkBundle + DoctrineBundle (in-memory SQLite) + this bundle.
 */
final class ScopedEntityTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new NowoControllerKitBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 3);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/controller-kit-bundle-tests/' . md5(self::class) . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/controller-kit-bundle-tests/' . md5(self::class) . '/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret'               => 'test',
            'test'                 => true,
            'http_method_override' => false,
            'router'               => ['utf8' => true],
        ]);

        $orm = [
            'mappings' => [
                'Fixtures' => [
                    'type'      => 'attribute',
                    'is_bundle' => false,
                    'dir'       => '%kernel.project_dir%/tests/Fixtures/Entity',
                    'prefix'    => 'Nowo\ControllerKitBundle\Tests\Fixtures\Entity',
                ],
            ],
        ];
        if (PHP_VERSION_ID >= 80400) {
            $orm['enable_native_lazy_objects'] = true;
        }

        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'memory' => true,
                'types'  => [StrictCodeType::NAME => StrictCodeType::class],
            ],
            'orm' => $orm,
        ]);

        $services = $container->services();
        $services->set('logger', NullLogger::class);
        $services->set(ItemController::class)
            ->public()
            ->tag('controller.service_arguments');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('item_by_id', '/owners/{ownerId}/items/{itemId}')->controller([ItemController::class, 'showById']);
        $routes->add('item_by_code', '/o/{ownerSlug}/items/{code}')->controller([ItemController::class, 'showByCode']);
        $routes->add('item_optional', '/o/{ownerSlug}/optional/{code}')->controller([ItemController::class, 'optional']);
    }
}
