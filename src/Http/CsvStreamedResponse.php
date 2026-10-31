<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Http;

use Closure;
use InvalidArgumentException;
use Stringable;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function fclose;
use function fopen;
use function fwrite;

/**
 * A formula-injection-safe CSV file streamed to the client row by row (no full export kept in memory).
 *
 * Every cell goes through {@see CsvSafeWriter}. The rows are an iterable or a closure returning one (a generator
 * reading the database in batches is evaluated only while the body is sent). Optional UTF-8 BOM (Excel), field
 * delimiter, `Cache-Control: no-store, private`, and a safe `Content-Disposition: attachment` header.
 *
 * The callback captures only its constructor arguments: nothing outlives the response (FrankenPHP worker-safe).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CsvStreamedResponse extends StreamedResponse
{
    /**
     * @param string $filename File name offered to the browser (sanitised)
     * @param Closure(): iterable<array<array-key, float|int|string|Stringable|null>>|iterable<array<array-key, float|int|string|Stringable|null>> $rows Data rows
     * @param list<float|int|string|Stringable|null> $header Header row (written first; none when empty)
     * @param bool $bom Write the UTF-8 byte order mark first
     * @param string $delimiter One single-byte field delimiter (`,`, `;`, tab)
     * @param bool $noStore Send `Cache-Control: no-store, private`
     * @param bool $keepNumericStrings Leave plain decimal strings (`-12.50`) unguarded
     * @param array<string, list<string|null>|string|null> $headers Extra response headers
     *
     * @throws InvalidArgumentException when the delimiter is not usable
     */
    public function __construct(
        string $filename,
        iterable|Closure $rows,
        array $header = [],
        bool $bom = true,
        string $delimiter = ',',
        bool $noStore = false,
        bool $keepNumericStrings = false,
        array $headers = [],
    ) {
        CsvSafeWriter::assertDelimiter($delimiter);
        $headers['Content-Disposition'] = AttachmentDisposition::attachment($filename);
        if ($noStore) {
            $headers['Cache-Control'] = 'no-store, private';
        }

        parent::__construct(
            static function () use ($rows, $header, $bom, $delimiter, $keepNumericStrings): void {
                $out = fopen('php://output', 'w');
                if ($out === false) {
                    return; // @codeCoverageIgnore
                }
                if ($bom) {
                    fwrite($out, CsvSafeWriter::BOM);
                }
                if ($header !== []) {
                    CsvSafeWriter::write($out, $header, $delimiter, $keepNumericStrings);
                }
                foreach ($rows instanceof Closure ? $rows() : $rows as $row) {
                    CsvSafeWriter::write($out, $row, $delimiter, $keepNumericStrings);
                }
                fclose($out);
            },
            self::HTTP_OK,
            ['Content-Type' => 'text/csv; charset=UTF-8'] + $headers,
        );
    }
}
