<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

use function is_string;
use function sprintf;

/**
 * String column that rejects malformed codes on the way to the database, like a UUID type does.
 */
final class StrictCodeType extends StringType
{
    public const NAME = 'strict_code';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (!is_string($value) || preg_match('/^[A-Z]+-\d+$/', $value) !== 1) {
            throw new ConversionException(sprintf('Malformed code "%s".', get_debug_type($value)));
        }

        return $value;
    }
}
