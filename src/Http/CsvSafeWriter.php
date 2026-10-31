<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Http;

use InvalidArgumentException;
use Stringable;

use function array_map;
use function array_values;
use function fputcsv;
use function is_string;
use function preg_match;
use function sprintf;
use function strlen;

/**
 * CSV rows that are safe to open in a spreadsheet (CSV / formula injection, OWASP).
 *
 * A text cell starting with `=`, `+`, `-`, `@`, a tab or a carriage return gets a leading `'`, so Excel,
 * LibreOffice or Google Sheets never evaluate it as a formula (`=HYPERLINK(…)`, `=cmd|' /C calc'!A0`, …).
 * Numbers (`int` / `float`) are written as they are: a number is never a formula. Numeric strings
 * (`-12.50`) are guarded too unless `$keepNumericStrings` is set (amount columns that must stay numbers).
 *
 * Static and stateless: FrankenPHP worker-safe (no memoised state between requests).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class CsvSafeWriter
{
    /** UTF-8 byte order mark: Excel then reads the file as UTF-8. */
    public const BOM = "\xEF\xBB\xBF";

    /** First characters a spreadsheet may evaluate as a formula (OWASP CSV injection). */
    public const FORMULA_PREFIX_PATTERN = '/^[=+\-@\t\r]/';

    /** A plain decimal number written as text (`12`, `-3`, `12.50`); kept as-is with `$keepNumericStrings`. */
    public const NUMERIC_STRING_PATTERN = '/^-?\d+(\.\d+)?$/';

    /**
     * One CSV cell: `null` is an empty cell, numbers are written as they are, text is guarded.
     *
     * @param float|int|string|Stringable|null $value Cell value
     * @param bool $keepNumericStrings Leave plain decimal strings (`-12.50`) unguarded
     *
     * @return string The cell text, prefixed with `'` when it could be read as a formula
     */
    public static function cell(int|float|string|Stringable|null $value, bool $keepNumericStrings = false): string
    {
        if ($value === null) {
            return '';
        }
        if (!is_string($value) && !$value instanceof Stringable) {
            return (string) $value;
        }

        $text = (string) $value;
        if ($text === '' || preg_match(self::FORMULA_PREFIX_PATTERN, $text) !== 1) {
            return $text;
        }
        if ($keepNumericStrings && preg_match(self::NUMERIC_STRING_PATTERN, $text) === 1) {
            return $text;
        }

        return "'" . $text;
    }

    /**
     * Guards every cell of a row.
     *
     * @param array<array-key, float|int|string|Stringable|null> $row Row values
     * @param bool $keepNumericStrings Leave plain decimal strings unguarded
     *
     * @return list<string> Guarded cells, in order
     */
    public static function row(array $row, bool $keepNumericStrings = false): array
    {
        return array_map(
            static fn (int|float|string|Stringable|null $value): string => self::cell($value, $keepNumericStrings),
            array_values($row),
        );
    }

    /**
     * Writes one guarded row with `fputcsv()` (RFC 4180 quoting, no escape character).
     *
     * @param resource $handle Writable stream (`php://output`, `php://temp`, a file)
     * @param array<array-key, float|int|string|Stringable|null> $row Row values
     * @param string $delimiter One single-byte field delimiter (`,`, `;`, tab)
     * @param bool $keepNumericStrings Leave plain decimal strings unguarded
     *
     * @throws InvalidArgumentException when the delimiter is not a single byte or clashes with the quoting
     */
    public static function write($handle, array $row, string $delimiter = ',', bool $keepNumericStrings = false): void
    {
        self::assertDelimiter($delimiter);
        fputcsv($handle, self::row($row, $keepNumericStrings), $delimiter, '"', '');
    }

    /**
     * Validates a CSV field delimiter: one byte, not the `"` enclosure and not a line break.
     *
     * @param string $delimiter Delimiter to check
     *
     * @throws InvalidArgumentException when the delimiter is not usable
     */
    public static function assertDelimiter(string $delimiter): void
    {
        if (strlen($delimiter) !== 1 || $delimiter === '"' || $delimiter === "\n" || $delimiter === "\r") {
            throw new InvalidArgumentException(sprintf('The CSV delimiter must be one byte other than a double quote or a line break, "%s" given.', $delimiter));
        }
    }
}
