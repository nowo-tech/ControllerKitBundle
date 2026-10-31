<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Http;

use JsonException;
use JsonSerializable;
use Nowo\ControllerKitBundle\Http\JsonAttachmentResponse;
use PHPUnit\Framework\TestCase;

use const JSON_UNESCAPED_SLASHES;

final class JsonAttachmentResponseTest extends TestCase
{
    public function testPrettyJsonAttachment(): void
    {
        $response = new JsonAttachmentResponse(['url' => 'https://x/y', 'n' => 1], 'project-demo.json');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame("{\n    \"url\": \"https://x/y\",\n    \"n\": 1\n}\n", $response->getContent());
        self::assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename=project-demo.json', $response->headers->get('Content-Disposition'));
        self::assertStringNotContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function testNoStoreAndUnsafeFilenamesGetAnAsciiFallback(): void
    {
        $response = new JsonAttachmentResponse([], 'café "x".json', noStore: true);

        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));
        self::assertSame("[]\n", $response->getContent());
        self::assertSame(
            'attachment; filename="caf_ _x_.json"; filename*=utf-8\'\'caf%C3%A9%20%22x%22.json',
            $response->headers->get('Content-Disposition'),
        );
    }

    public function testJsonSerializableEncodingOptionsAndExtraHeaders(): void
    {
        $payload = new class implements JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return ['path' => '/a/b'];
            }
        };

        $response = new JsonAttachmentResponse($payload, 'x.json', encodingOptions: JSON_UNESCAPED_SLASHES, headers: ['X-Export' => 'yes']);

        self::assertSame("{\"path\":\"/a/b\"}\n", $response->getContent());
        self::assertSame('yes', $response->headers->get('X-Export'));
    }

    public function testUnencodablePayloadThrows(): void
    {
        $this->expectException(JsonException::class);

        new JsonAttachmentResponse(['bad' => "\xB1\x31"], 'x.json');
    }

    public function testAttachmentShortcut(): void
    {
        self::assertSame('attachment; filename=a.pdf', JsonAttachmentResponse::attachment('a.pdf'));
    }
}
