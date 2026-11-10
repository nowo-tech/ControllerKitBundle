<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\ValueResolver;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use LogicException;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;

use function class_exists;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;

/**
 * The built-in `#[MapScopedEntity]` lookup (spec 003): one Doctrine ORM query scoped to the owner (2.4+ public so a
 * host {@see ScopedEntityFinderInterface} can delegate to it).
 *
 * The owner (`parent`) is either an association, joined in the query, or (2.4+) a plain mapped field holding the
 * owner id, compared directly. Mapping errors are `LogicException`s raised before any query. Not tagged as a finder:
 * the resolver runs it when no tagged finder supports the class.
 *
 * Stateless and FrankenPHP worker-safe.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class DoctrineScopedEntityFinder implements ScopedEntityFinderInterface
{
    /**
     * @param ManagerRegistry $registry Doctrine registry used to pick the entity manager
     */
    public function __construct(
        private ManagerRegistry $registry,
    ) {
    }

    /**
     * True when `$class` is a Doctrine ORM entity of the attribute's (or its own) entity manager.
     *
     * @param class-string $class argument type of the controller parameter
     * @param MapScopedEntity $attribute the attribute
     */
    public function supports(string $class, MapScopedEntity $attribute): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        try {
            $manager = $this->manager($class, $attribute);
        } catch (InvalidArgumentException) {
            return false; // unknown named manager
        }

        return $manager instanceof EntityManagerInterface && !$manager->getMetadataFactory()->isTransient($class);
    }

    /**
     * Runs the owner-scoped query; null when not found, owned by another owner or malformed for the column type.
     *
     * @param class-string $class argument type of the controller parameter
     * @param int|string $id route value named by `MapScopedEntity::$id`
     * @param int|string $ownerId route value named by `MapScopedEntity::getParentRouteAttribute()`
     * @param MapScopedEntity $attribute the attribute
     *
     * @throws LogicException on a mapping error
     */
    public function findScoped(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute): ?object
    {
        return $this->query($class, $id, $ownerId, $attribute, $this->plan($class, $attribute, null));
    }

    /**
     * Resolver entry point: checks the mapping first (before any route value is read, as in 2.3), then queries when
     * both route values are non-empty strings or ints.
     *
     * @internal used by {@see ScopedEntityValueResolver}
     *
     * @param class-string $class argument type of the controller parameter
     * @param mixed $id raw route value of the entity
     * @param mixed $ownerId raw route value of the owner
     * @param MapScopedEntity $attribute the attribute
     * @param string $argument controller argument name, quoted in the messages
     *
     * @throws LogicException on a mapping error
     */
    public function findForRoute(string $class, mixed $id, mixed $ownerId, MapScopedEntity $attribute, string $argument): ?object
    {
        $plan = $this->plan($class, $attribute, $argument);
        if (!self::isIdentifier($id) || !self::isIdentifier($ownerId)) {
            return null;
        }

        return $this->query($class, $id, $ownerId, $attribute, $plan);
    }

    /**
     * Whether a route value can be an identifier: a non-empty string or an int.
     *
     * @phpstan-assert-if-true int|string $value
     */
    public static function isIdentifier(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && $value !== '');
    }

    /**
     * @param class-string $class
     * @param array{EntityManagerInterface, ClassMetadata<object>, ClassMetadata<object>|null} $plan
     */
    private function query(string $class, int|string $id, int|string $ownerId, MapScopedEntity $attribute, array $plan): ?object
    {
        [$manager, $metadata, $parentMetadata] = $plan;

        try {
            $query = $manager->createQueryBuilder()
                ->select('e')
                ->from($class, 'e')
                ->andWhere(sprintf('e.%s = :id', $attribute->field))
                ->setParameter('id', $id, $metadata->getTypeOfField($attribute->field));
            if (!$parentMetadata instanceof ClassMetadata) {
                $query
                    ->andWhere(sprintf('e.%s = :parentId', $attribute->parent))
                    ->setParameter('parentId', $ownerId, $metadata->getTypeOfField($attribute->parent));
            } else {
                $query
                    ->innerJoin('e.' . $attribute->parent, 'p')
                    ->andWhere(sprintf('p.%s = :parentId', $attribute->parentField))
                    ->setParameter('parentId', $ownerId, $parentMetadata->getTypeOfField($attribute->parentField));
            }

            $entity = $query->getQuery()->getOneOrNullResult();
        } catch (ConversionException) {
            // Malformed identifier for the column type (e.g. not a UUID): same answer as "not found".
            return null;
        }

        return is_object($entity) ? $entity : null;
    }

    /**
     * @param class-string $class
     *
     * @return array{EntityManagerInterface, ClassMetadata<object>, ClassMetadata<object>|null}
     */
    private function plan(string $class, MapScopedEntity $attribute, ?string $argument): array
    {
        $suffix  = $argument === null ? '' : sprintf(' ("$%s")', $argument);
        $manager = $this->manager($class, $attribute);
        if (!$manager instanceof EntityManagerInterface) {
            throw new LogicException(sprintf('#[MapScopedEntity] needs a Doctrine ORM entity manager for "%s"%s.', $class, $suffix));
        }

        $metadata = $manager->getClassMetadata($class);
        if (!$metadata->hasAssociation($attribute->parent)) {
            // 2.4+: an owner id stored in a plain column of the entity.
            if (!isset($metadata->fieldMappings[$attribute->parent])) {
                throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no association "%s"%s.', $class, $attribute->parent, $suffix));
            }
            if ($metadata->isIdentifier($attribute->parent)) {
                throw new LogicException(sprintf('#[MapScopedEntity] owner field "%s" of "%s" is its identifier, which scopes nothing%s.', $attribute->parent, $class, $suffix));
            }
            if ($attribute->parent === $attribute->field) {
                throw new LogicException(sprintf('#[MapScopedEntity] owner field "%s" of "%s" is also the lookup field%s.', $attribute->parent, $class, $suffix));
            }
            if ($attribute->getParentRouteAttribute() === $attribute->id) {
                throw new LogicException(sprintf('#[MapScopedEntity] owner and entity of "%s" read the same route attribute "%s"%s.', $class, $attribute->id, $suffix));
            }
        }

        if (!$metadata->hasField($attribute->field)) {
            throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no field "%s"%s.', $class, $attribute->field, $suffix));
        }

        $parentMetadata = null;
        if ($metadata->hasAssociation($attribute->parent)) {
            $parentMetadata = $manager->getClassMetadata($metadata->getAssociationTargetClass($attribute->parent));
            if (!$parentMetadata->hasField($attribute->parentField)) {
                throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no field "%s"%s.', $parentMetadata->getName(), $attribute->parentField, $suffix));
            }
        }

        return [$manager, $metadata, $parentMetadata];
    }

    /**
     * @param class-string $class
     */
    private function manager(string $class, MapScopedEntity $attribute): ?object
    {
        return $attribute->objectManager === null
            ? $this->registry->getManagerForClass($class)
            : $this->registry->getManager($attribute->objectManager);
    }
}
