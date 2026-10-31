<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Http;

use Nowo\ControllerKitBundle\Http\AttachmentDisposition;
use PHPUnit\Framework\TestCase;

final class AttachmentDispositionTest extends TestCase
{
    public function testPlainAsciiName(): void
    {
        self::assertSame('attachment; filename=export.csv', AttachmentDisposition::attachment('export.csv'));
        self::assertSame('inline; filename=report.pdf', AttachmentDisposition::inline('report.pdf'));
    }

    public function testQuotesAndNonAsciiGetAnAsciiFallback(): void
    {
        self::assertSame(
            'attachment; filename="caf_ _x_.json"; filename*=utf-8\'\'caf%C3%A9%20%22x%22.json',
            AttachmentDisposition::attachment('café "x".json'),
        );
        self::assertSame('attachment; filename=100_.csv; filename*=utf-8\'\'100%25.csv', AttachmentDisposition::attachment('100%.csv'));
    }

    public function testPathSeparatorsAndControlCharactersNeverReachTheHeader(): void
    {
        $header = AttachmentDisposition::attachment("../../etc/passwd\r\nX-Injected: 1");

        self::assertStringNotContainsString('/', $header);
        self::assertStringNotContainsString("\r", $header);
        self::assertStringNotContainsString("\n", $header);
        self::assertSame('attachment; filename=".._.._etc_passwd__X-Injected: 1"', $header);
        self::assertSame('a_b_c.txt', AttachmentDisposition::safeFilename('a/b\\c.txt'));
    }

    public function testEmptyOrInvalidNamesFallBackToDownload(): void
    {
        self::assertSame('download', AttachmentDisposition::safeFilename(''));
        self::assertSame('download', AttachmentDisposition::safeFilename('   '));
        self::assertSame('download', AttachmentDisposition::safeFilename("\xFF\xFE.csv"));
        self::assertSame('attachment; filename=download', AttachmentDisposition::attachment(''));
    }

    public function testAsciiFallback(): void
    {
        self::assertSame('a_b_c_d_e.txt', AttachmentDisposition::asciiFallback('a"b%c/d\\e.txt'));
        self::assertSame('_.txt', AttachmentDisposition::asciiFallback('ñ.txt'));
    }
}
