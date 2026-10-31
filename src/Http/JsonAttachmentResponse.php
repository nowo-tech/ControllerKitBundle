<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Http;

use JsonException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * JSON served as a file download (config / data / account exports).
 *
 * The body is pretty-printed JSON ending with a newline, `Content-Type: application/json; charset=UTF-8`,
 * and a safe `Content-Disposition: attachment` header built by {@see AttachmentDisposition}. With `$noStore`
 * the response is `Cache-Control: no-store, private`, so exports carrying secrets or personal data are never
 * kept by a browser or proxy cache.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class JsonAttachmentResponse extends Response
{
    /** Default `json_encode()` flags (`JSON_THROW_ON_ERROR` is always added). */
    public const DEFAULT_ENCODING_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

    /**
     * @param array<array-key, mixed>|JsonSerializable $payload Data to encode
     * @param string $filename File name offered to the browser (sanitised)
     * @param bool $noStore Send `Cache-Control: no-store, private` (secrets, personal data)
     * @param int $encodingOptions `json_encode()` flags
     * @param array<string, list<string|null>|string|null> $headers Extra response headers
     *
     * @throws JsonException when the payload cannot be encoded
     */
    public function __construct(
        array|JsonSerializable $payload,
        string $filename,
        bool $noStore = false,
        int $encodingOptions = self::DEFAULT_ENCODING_OPTIONS,
        array $headers = [],
    ) {
        $headers['Content-Disposition'] = AttachmentDisposition::attachment($filename);
        if ($noStore) {
            $headers['Cache-Control'] = 'no-store, private';
        }

        // Headers go through the constructor: the response never mutates its header bag afterwards.
        parent::__construct(
            json_encode($payload, $encodingOptions | JSON_THROW_ON_ERROR) . "\n",
            self::HTTP_OK,
            ['Content-Type' => 'application/json; charset=UTF-8'] + $headers,
        );
    }

    /**
     * Shortcut for {@see AttachmentDisposition::attachment()} (kept for the other download responses).
     *
     * @param string $filename File name offered to the browser
     *
     * @return string Header value for `Content-Disposition`
     */
    public static function attachment(string $filename): string
    {
        return AttachmentDisposition::attachment($filename);
    }
}
