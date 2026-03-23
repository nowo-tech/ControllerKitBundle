<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Integration\DependencyInjection;

use Nowo\ControllerKitBundle\DependencyInjection\ControllerKitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ControllerKitExtensionIntegrationTest extends TestCase
{
    public function testExtensionRegistersParameterInCompiledContainer(): void
    {
        $container = new ContainerBuilder();
        $extension = new ControllerKitExtension();

        $container->registerExtension($extension);
        $container->loadFromExtension('nowo_controller_kit', ['default_route' => 'integration_home']);
        $container->compile();

        self::assertTrue($container->hasParameter('nowo_controller_kit.default_route'));
        self::assertSame('integration_home', $container->getParameter('nowo_controller_kit.default_route'));
    }
}
