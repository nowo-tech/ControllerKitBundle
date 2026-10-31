<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Controller;

use Closure;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use Nowo\ControllerKitBundle\Http\CsvStreamedResponse;
use Nowo\ControllerKitBundle\Http\JsonAttachmentResponse;
use Stringable;
use Throwable;

use function is_bool;
use function is_string;
use function method_exists;

/**
 * Download helpers for controllers: `csvDownload()` (formula-injection-safe streamed CSV) and `jsonDownload()`
 * (pretty JSON attachment).
 *
 * Arguments left `null` take the defaults of `nowo_controller_kit.downloads` (CSV delimiter, BOM, `no-store`)
 * when the controller extends `AbstractController`; otherwise the built-in defaults (`,`, BOM on, cacheable).
 *
 * Stateless and FrankenPHP worker-safe (including when the kernel is not reset between requests): no properties
 * or statics; the CSV rows are only read while the response body is sent.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait DownloadResponseTrait
{
    /**
     * Streams a CSV attachment; every cell is guarded against formula injection.
     *
     * @param string $filename File name offered to the browser (sanitised)
     * @param Closure(): iterable<array<array-key, float|int|string|Stringable|null>>|iterable<array<array-key, float|int|string|Stringable|null>> $rows Data rows (a generator is read lazily)
     * @param list<float|int|string|Stringable|null> $header Header row (none when empty)
     * @param bool|null $noStore `Cache-Control: no-store, private` (null: `downloads.no_store`)
     * @param string|null $delimiter Field delimiter (null: `downloads.csv.delimiter`)
     * @param bool|null $bom UTF-8 byte order mark (null: `downloads.csv.bom`)
     * @param bool $keepNumericStrings Leave plain decimal strings (`-12.50`) unguarded
     *
     * @throws InvalidArgumentException when the delimiter is not usable
     */
    protected function csvDownload(
        string $filename,
        iterable|Closure $rows,
        array $header = [],
        ?bool $noStore = null,
        ?string $delimiter = null,
        ?bool $bom = null,
        bool $keepNumericStrings = false,
    ): CsvStreamedResponse {
        return new CsvStreamedResponse(
            $filename,
            $rows,
            $header,
            $bom ?? $this->downloadBoolOption('nowo_controller_kit.downloads.csv_bom', true),
            $delimiter ?? $this->downloadStringOption('nowo_controller_kit.downloads.csv_delimiter', ','),
            $noStore ?? $this->downloadBoolOption('nowo_controller_kit.downloads.no_store', false),
            $keepNumericStrings,
        );
    }

    /**
     * Pretty JSON attachment.
     *
     * @param array<array-key, mixed>|JsonSerializable $payload Data to encode
     * @param string $filename File name offered to the browser (sanitised)
     * @param bool|null $noStore `Cache-Control: no-store, private` (null: `downloads.no_store`)
     *
     * @throws JsonException when the payload cannot be encoded
     */
    protected function jsonDownload(array|JsonSerializable $payload, string $filename, ?bool $noStore = null): JsonAttachmentResponse
    {
        return new JsonAttachmentResponse(
            $payload,
            $filename,
            $noStore ?? $this->downloadBoolOption('nowo_controller_kit.downloads.no_store', false),
        );
    }

    private function downloadBoolOption(string $name, bool $default): bool
    {
        $value = $this->downloadParameter($name);

        return is_bool($value) ? $value : $default;
    }

    private function downloadStringOption(string $name, string $default): string
    {
        $value = $this->downloadParameter($name);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * Reads a bundle parameter through `AbstractController::getParameter()`; null when unavailable.
     */
    private function downloadParameter(string $name): mixed
    {
        if (!method_exists($this, 'getParameter')) {
            return null;
        }

        try {
            return $this->getParameter($name);
        } catch (Throwable) {
            return null;
        }
    }
}
