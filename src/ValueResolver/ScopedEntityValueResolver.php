<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\ValueResolver;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function is_int;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Resolves {@see MapScopedEntity} arguments with one Doctrine ORM query joined on the owner
 * association (spec 003): a missing entity, an entity of another owner and a malformed identifier
 * are the same 404 (or `null` for a nullable argument).
 *
 * Registered as a targeted value resolver only when doctrine/orm and the `doctrine` registry
 * (DoctrineBundle) are available. Stateless and FrankenPHP worker-safe.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class ScopedEntityValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ManagerRegistry $registry,
    ) {
    }

    /**
     * @return iterable<object|null>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attribute = $argument->getAttributesOfType(MapScopedEntity::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null;
        if (!$attribute instanceof MapScopedEntity) {
            return [];
        }

        $class = $argument->getType();
        if ($class === null || !class_exists($class)) {
            throw new LogicException(sprintf('#[MapScopedEntity] needs an entity-typed argument ("$%s").', $argument->getName()));
        }

        $manager = $attribute->objectManager === null
            ? $this->registry->getManagerForClass($class)
            : $this->registry->getManager($attribute->objectManager);
        if (!$manager instanceof EntityManagerInterface) {
            throw new LogicException(sprintf('#[MapScopedEntity] needs a Doctrine ORM entity manager for "%s" ("$%s").', $class, $argument->getName()));
        }

        $metadata = $manager->getClassMetadata($class);
        if (!$metadata->hasAssociation($attribute->parent)) {
            throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no association "%s" ("$%s").', $class, $attribute->parent, $argument->getName()));
        }

        if (!$metadata->hasField($attribute->field)) {
            throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no field "%s" ("$%s").', $class, $attribute->field, $argument->getName()));
        }

        $parentMetadata = $manager->getClassMetadata($metadata->getAssociationTargetClass($attribute->parent));
        if (!$parentMetadata->hasField($attribute->parentField)) {
            throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no field "%s" ("$%s").', $parentMetadata->getName(), $attribute->parentField, $argument->getName()));
        }

        $id       = $request->attributes->get($attribute->id);
        $parentId = $request->attributes->get($attribute->getParentRouteAttribute());

        $entity = null;
        if ($this->isIdentifier($id) && $this->isIdentifier($parentId)) {
            try {
                $entity = $manager->createQueryBuilder()
                    ->select('e')
                    ->from($class, 'e')
                    ->innerJoin('e.' . $attribute->parent, 'p')
                    ->andWhere(sprintf('e.%s = :id', $attribute->field))
                    ->andWhere(sprintf('p.%s = :parentId', $attribute->parentField))
                    ->setParameter('id', $id, $metadata->getTypeOfField($attribute->field))
                    ->setParameter('parentId', $parentId, $parentMetadata->getTypeOfField($attribute->parentField))
                    ->getQuery()
                    ->getOneOrNullResult();
            } catch (ConversionException) {
                // Malformed identifier for the column type (e.g. not a UUID): same answer as "not found".
                $entity = null;
            }
        }

        if (!is_object($entity) && !$argument->isNullable()) {
            throw new NotFoundHttpException(sprintf('"%s" object not found by "%s".', $class, self::class));
        }

        return [is_object($entity) ? $entity : null];
    }

    private function isIdentifier(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && $value !== '');
    }
}
