<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Controller;

use Nowo\ControllerKitBundle\Controller\DownloadResponseTrait;
use Nowo\ControllerKitBundle\Http\CsvStreamedResponse;
use Nowo\ControllerKitBundle\Http\JsonAttachmentResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use function ob_get_clean;
use function ob_start;

final class DownloadResponseTraitTest extends TestCase
{
    public function testDefaultsWithoutAbstractController(): void
    {
        $controller = new PlainDownloadController();

        $csv = $controller->csvFile('x.csv', [['=x']], ['h']);
        self::assertSame("\xEF\xBB\xBFh\n'=x\n", $this->body($csv));
        self::assertStringNotContainsString('no-store', (string) $csv->headers->get('Cache-Control'));

        $json = $controller->jsonFile(['a' => 1], 'x.json');
        self::assertStringNotContainsString('no-store', (string) $json->headers->get('Cache-Control'));
    }

    public function testConfiguredDefaultsComeFromTheBundleParameters(): void
    {
        $controller = $this->abstractController([
            'nowo_controller_kit.downloads.no_store'      => true,
            'nowo_controller_kit.downloads.csv_delimiter' => ';',
            'nowo_controller_kit.downloads.csv_bom'       => false,
        ]);

        $csv = $controller->csvFile('x.csv', [['a', '+b']]);
        self::assertSame("a;'+b\n", $this->body($csv));
        self::assertSame('no-store, private', $csv->headers->get('Cache-Control'));

        $json = $controller->jsonFile(['a' => 1], 'x.json');
        self::assertSame('no-store, private', $json->headers->get('Cache-Control'));
    }

    public function testExplicitArgumentsWinOverTheParameters(): void
    {
        $controller = $this->abstractController([
            'nowo_controller_kit.downloads.no_store'      => true,
            'nowo_controller_kit.downloads.csv_delimiter' => ';',
            'nowo_controller_kit.downloads.csv_bom'       => false,
        ]);

        $csv = $controller->csvFile('x.csv', [['-1', 'b']], [], false, "\t", true, true);
        self::assertSame("\xEF\xBB\xBF-1\tb\n", $this->body($csv));
        self::assertStringNotContainsString('no-store', (string) $csv->headers->get('Cache-Control'));

        $json = $controller->jsonFile(['a' => 1], 'x.json', false);
        self::assertStringNotContainsString('no-store', (string) $json->headers->get('Cache-Control'));
    }

    public function testMissingOrInvalidParametersFallBackToBuiltInDefaults(): void
    {
        $controller = $this->abstractController([
            'nowo_controller_kit.downloads.no_store'      => 'yes',
            'nowo_controller_kit.downloads.csv_delimiter' => '',
        ]);

        $csv = $controller->csvFile('x.csv', [['a', 'b']]);
        self::assertSame("\xEF\xBB\xBFa,b\n", $this->body($csv));
        self::assertStringNotContainsString('no-store', (string) $csv->headers->get('Cache-Control'));
    }

    public function testAbstractControllerWithoutContainerFallsBackToBuiltInDefaults(): void
    {
        $csv = (new AbstractDownloadController())->csvFile('x.csv', [['a']]);

        self::assertSame("\xEF\xBB\xBFa\n", $this->body($csv));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function abstractController(array $parameters): AbstractDownloadController
    {
        $container = new Container(new ParameterBag($parameters));
        $container->set('parameter_bag', new ContainerBag($container));

        $controller = new AbstractDownloadController();
        $controller->setContainer($container);

        return $controller;
    }

    private function body(CsvStreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}

final class PlainDownloadController
{
    use DownloadResponseTrait;

    /**
     * @param iterable<array<array-key, string|null>> $rows
     * @param list<string> $header
     */
    public function csvFile(string $filename, iterable $rows, array $header = []): CsvStreamedResponse
    {
        return $this->csvDownload($filename, $rows, $header);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function jsonFile(array $payload, string $filename): JsonAttachmentResponse
    {
        return $this->jsonDownload($payload, $filename);
    }
}

final class AbstractDownloadController extends AbstractController
{
    use DownloadResponseTrait;

    /**
     * @param iterable<array<array-key, string|null>> $rows
     * @param list<string> $header
     */
    public function csvFile(
        string $filename,
        iterable $rows,
        array $header = [],
        ?bool $noStore = null,
        ?string $delimiter = null,
        ?bool $bom = null,
        bool $keepNumericStrings = false,
    ): CsvStreamedResponse {
        return $this->csvDownload($filename, $rows, $header, $noStore, $delimiter, $bom, $keepNumericStrings);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function jsonFile(array $payload, string $filename, ?bool $noStore = null): JsonAttachmentResponse
    {
        return $this->jsonDownload($payload, $filename, $noStore);
    }
}
