<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\DependencyInjection;

use Nowo\ControllerKitBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testDownloadDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertSame(['no_store' => false, 'csv' => ['delimiter' => ',', 'bom' => true]], $config['downloads']);
    }

    public function testDownloadDefaultsCanBeOverridden(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'downloads' => ['no_store' => true, 'csv' => ['delimiter' => "\t", 'bom' => false]],
        ]]);

        self::assertSame(['no_store' => true, 'csv' => ['delimiter' => "\t", 'bom' => false]], $config['downloads']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidDelimiters(): iterable
    {
        yield 'two bytes' => [';;'];
        yield 'quote' => ['"'];
        yield 'line feed' => ["\n"];
        yield 'not a string' => [1];
    }

    #[DataProvider('invalidDelimiters')]
    public function testInvalidCsvDelimiterIsRejected(mixed $delimiter): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [['downloads' => ['csv' => ['delimiter' => $delimiter]]]]);
    }
}
