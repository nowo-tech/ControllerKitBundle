<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\DependencyInjection;

use Nowo\ControllerKitBundle\DependencyInjection\ControllerKitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ControllerKitExtensionTest extends TestCase
{
    public function testGetAliasReturnsExpectedValue(): void
    {
        $extension = new ControllerKitExtension();

        self::assertSame('nowo_controller_kit', $extension->getAlias());
    }

    public function testLoadSetsDefaultRouteParameterWhenConfigIsMissing(): void
    {
        $extension = new ControllerKitExtension();
        $container = new ContainerBuilder();

        $extension->load([], $container);

        self::assertTrue($container->hasParameter('nowo_controller_kit.default_route'));
        self::assertSame('homepage', $container->getParameter('nowo_controller_kit.default_route'));
    }

    public function testLoadSetsCustomDefaultRouteParameter(): void
    {
        $extension = new ControllerKitExtension();
        $container = new ContainerBuilder();

        $extension->load([['default_route' => 'app_landing']], $container);

        self::assertSame('app_landing', $container->getParameter('nowo_controller_kit.default_route'));
    }

    public function testLoadSetsDownloadParameters(): void
    {
        $container = new ContainerBuilder();
        (new ControllerKitExtension())->load([['downloads' => ['no_store' => true, 'csv' => ['delimiter' => ';', 'bom' => false]]]], $container);

        self::assertTrue($container->getParameter('nowo_controller_kit.downloads.no_store'));
        self::assertSame(';', $container->getParameter('nowo_controller_kit.downloads.csv_delimiter'));
        self::assertFalse($container->getParameter('nowo_controller_kit.downloads.csv_bom'));
    }

    public function testLoadSetsDefaultDownloadParameters(): void
    {
        $container = new ContainerBuilder();
        (new ControllerKitExtension())->load([], $container);

        self::assertFalse($container->getParameter('nowo_controller_kit.downloads.no_store'));
        self::assertSame(',', $container->getParameter('nowo_controller_kit.downloads.csv_delimiter'));
        self::assertTrue($container->getParameter('nowo_controller_kit.downloads.csv_bom'));
    }
}
