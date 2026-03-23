<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\DependencyInjection;

use Nowo\ControllerKitBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultRouteIsHomepageWhenNotConfigured(): void
    {
        $processor = new Processor();
        $config    = $processor->processConfiguration(new Configuration(), []);

        self::assertSame('homepage', $config['default_route']);
    }

    public function testDefaultRouteCanBeOverridden(): void
    {
        $processor = new Processor();
        $config    = $processor->processConfiguration(
            new Configuration(),
            [['default_route' => 'app_dashboard']],
        );

        self::assertSame('app_dashboard', $config['default_route']);
    }

    public function testDefaultRouteCannotBeEmpty(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $processor = new Processor();
        $processor->processConfiguration(
            new Configuration(),
            [['default_route' => '']],
        );
    }
}
