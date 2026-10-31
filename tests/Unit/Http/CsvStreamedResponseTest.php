<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Http;

use Generator;
use InvalidArgumentException;
use Nowo\ControllerKitBundle\Http\CsvSafeWriter;
use Nowo\ControllerKitBundle\Http\CsvStreamedResponse;
use PHPUnit\Framework\TestCase;

use function ob_get_clean;
use function ob_start;

final class CsvStreamedResponseTest extends TestCase
{
    public function testStreamsBomHeaderAndGuardedRows(): void
    {
        $response = new CsvStreamedResponse('issues.csv', [['=1+1', 'ok'], ['@x', 3]], ['title', 'count']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename=issues.csv', $response->headers->get('Content-Disposition'));
        self::assertStringNotContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertSame(CsvSafeWriter::BOM . "title,count\n'=1+1,ok\n'@x,3\n", $this->body($response));
    }

    public function testRowsClosureIsEvaluatedOnlyWhileSending(): void
    {
        $calls = 0;
        $rows  = static function () use (&$calls): Generator {
            ++$calls;
            yield ['-1', 'a'];
            yield ['+2', 'b'];
        };

        $response = new CsvStreamedResponse('x.csv', $rows, bom: false, delimiter: ';', noStore: true, keepNumericStrings: true, headers: ['X-Export' => 'yes']);
        self::assertSame(0, $calls);

        self::assertSame("-1;a\n'+2;b\n", $this->body($response));
        self::assertSame(1, $calls);
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));
        self::assertSame('yes', $response->headers->get('X-Export'));
    }

    public function testNoHeaderRowWhenEmpty(): void
    {
        self::assertSame("'=x\n", $this->body(new CsvStreamedResponse('x.csv', [['=x']], bom: false)));
    }

    public function testInvalidDelimiterIsRejectedBeforeStreaming(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CsvStreamedResponse('x.csv', [], delimiter: '"');
    }

    private function body(CsvStreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}
