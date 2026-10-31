<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Http;

use Symfony\Component\HttpFoundation\HeaderUtils;

use function preg_match;
use function preg_replace;
use function trim;

/**
 * Builds a safe `Content-Disposition: attachment` header value for any download (CSV, JSON, PDF, ZIP, …).
 *
 * The header comes from {@see HeaderUtils::makeDisposition()}: a name with quotes or non-ASCII characters keeps
 * its UTF-8 form in `filename*` and gets an ASCII `filename` fallback (non-printable ASCII, `"`, `%`, `/`, `\`
 * become `_`) instead of a broken header. Path separators and control characters are also replaced in the
 * UTF-8 name, so a user-controlled name never makes the header throw nor points at a directory.
 *
 * Static and stateless: FrankenPHP worker-safe.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class AttachmentDisposition
{
    /** Name used when the given file name is empty (after trimming) or not valid UTF-8. */
    public const DEFAULT_FILENAME = 'download';

    /**
     * `attachment; filename=…` (plus `filename*=utf-8''…` when the name is not plain ASCII).
     *
     * @param string $filename File name offered to the browser (may be user-controlled)
     *
     * @return string Header value for `Content-Disposition`
     */
    public static function attachment(string $filename): string
    {
        return self::build(HeaderUtils::DISPOSITION_ATTACHMENT, $filename);
    }

    /**
     * `inline; filename=…`, for files the browser may display (PDF previews).
     *
     * @param string $filename File name offered to the browser (may be user-controlled)
     *
     * @return string Header value for `Content-Disposition`
     */
    public static function inline(string $filename): string
    {
        return self::build(HeaderUtils::DISPOSITION_INLINE, $filename);
    }

    /**
     * The UTF-8 file name actually sent: control characters, `/` and `\` replaced by `_`.
     *
     * @param string $filename Requested file name
     *
     * @return string Sanitised name, {@see DEFAULT_FILENAME} when nothing usable is left
     */
    public static function safeFilename(string $filename): string
    {
        if (preg_match('//u', $filename) !== 1) {
            return self::DEFAULT_FILENAME;
        }

        $safe = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '_', $filename));

        return $safe === '' ? self::DEFAULT_FILENAME : $safe;
    }

    /**
     * The ASCII `filename` fallback: non-printable ASCII, `"`, `%`, `/`, `\` replaced by `_`.
     *
     * @param string $filename Sanitised UTF-8 file name
     *
     * @return string ASCII fallback
     */
    public static function asciiFallback(string $filename): string
    {
        return (string) preg_replace('/[^\x20-\x7E]|["%\/\\\\]/u', '_', $filename);
    }

    private static function build(string $disposition, string $filename): string
    {
        $safe = self::safeFilename($filename);

        return HeaderUtils::makeDisposition($disposition, $safe, self::asciiFallback($safe));
    }
}
