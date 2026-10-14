<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Attribute;

use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use PHPUnit\Framework\TestCase;

final class MapScopedEntityTest extends TestCase
{
    public function testDefaults(): void
    {
        $attribute = new MapScopedEntity('project');

        self::assertSame('project', $attribute->parent);
        self::assertNull($attribute->parentId);
        self::assertSame('projectId', $attribute->getParentRouteAttribute());
        self::assertSame('id', $attribute->parentField);
        self::assertSame('id', $attribute->id);
        self::assertSame('id', $attribute->field);
        self::assertNull($attribute->objectManager);
        self::assertSame(ScopedEntityValueResolver::class, $attribute->resolver);
        self::assertFalse($attribute->disabled);
    }

    public function testExplicitParentRouteAttribute(): void
    {
        $attribute = new MapScopedEntity(parent: 'organization', parentId: 'orgSlug', parentField: 'slug', id: 'agendaId', field: 'uuid', objectManager: 'tenant');

        self::assertSame('orgSlug', $attribute->getParentRouteAttribute());
        self::assertSame('slug', $attribute->parentField);
        self::assertSame('agendaId', $attribute->id);
        self::assertSame('uuid', $attribute->field);
        self::assertSame('tenant', $attribute->objectManager);
    }
}
