<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\ValueResolver;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use LogicException;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;

use function class_exists;
use function is_int;
use function is_iterable;
use function is_object;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * The built-in `#[MapScopedEntity]` lookup (spec 003): one Doctrine ORM query scoped to the owner (2.4+ public so a
 * host {@see ScopedEntityFinderInterface} can delegate to it).
 *
 * The owner (`parent`) is either an association, joined in the query, or (2.4+) a plain mapped field holding the
 * owner id, compared directly. Mapping errors are `LogicException`s raised before any query. Not tagged as a finder:
 * the resolver runs it when no tagged finder supports the class.
 *
 * Since 2.4.1 (REQ-SEC-006, look-alike ids) the loaded entity's id field and owner field are re-checked against the
 * route values with `===` (string columns), so a folding host collation (`Bob` = `bob` = `bób`, `bob ` = `bob` on
 * MySQL / MariaDB) never serves another owner's row: a mismatch is "not found". The columns belong to the host; give
 * string id / owner columns a binary NO PAD collation (`utf8mb4_0900_bin` on MySQL 8.0.17+, `utf8mb4_nopad_bin` on
 * MariaDB), see docs/USAGE.md.
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
     * Runs the owner-scoped query; null when not found, owned by another owner or malformed for the column type, and
     * without any query when `$id` or `$ownerId` is an empty string.
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
        // Same rule as the resolver (isIdentifier()): an empty id or owner id never matches, so a row whose owner
        // column is '' is not served to a direct caller.
        if ($id === '' || $ownerId === '') {
            return null;
        }

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
                // The owner is fetch-joined so its field can be re-checked without a lazy load.
                $query
                    ->innerJoin('e.' . $attribute->parent, 'p')
                    ->addSelect('p')
                    ->andWhere(sprintf('p.%s = :parentId', $attribute->parentField))
                    ->setParameter('parentId', $ownerId, $parentMetadata->getTypeOfField($attribute->parentField));
            }

            $candidates = $query->getQuery()->getResult();
        } catch (ConversionException) {
            // Malformed identifier for the column type (e.g. not a UUID): same answer as "not found".
            return null;
        }

        // REQ-SEC-006 (look-alike ids): SQL compares with the host column collation, which on MySQL / MariaDB folds
        // case, accents and (PAD SPACE) trailing spaces. Keep only the rows whose id and owner match byte for byte.
        $found = null;
        foreach (is_iterable($candidates) ? $candidates : [] as $entity) {
            if (!is_object($entity) || !self::matches($metadata->getFieldValue($entity, $attribute->field), $id, $metadata->getTypeOfField($attribute->field))) {
                continue;
            }
            $owner = $parentMetadata instanceof ClassMetadata
                ? self::ownerField($parentMetadata, $metadata->getFieldValue($entity, $attribute->parent), $attribute->parentField)
                : $metadata->getFieldValue($entity, $attribute->parent);
            $ownerType = $parentMetadata instanceof ClassMetadata ? $parentMetadata->getTypeOfField($attribute->parentField) : $metadata->getTypeOfField($attribute->parent);
            if (!self::matches($owner, $ownerId, $ownerType)) {
                continue;
            }
            if ($found !== null) {
                throw new NonUniqueResultException(sprintf('#[MapScopedEntity] found more than one "%s" for that id and owner.', $class));
            }
            $found = $entity;
        }

        return $found;
    }

    /**
     * Whether a loaded value equals the requested route value exactly (REQ-SEC-006). Strings are compared byte for byte
     * (`===`); a `guid` column also accepts the other letter case of the same hexadecimal UUID. Other values (ints,
     * UUID / enum objects, ...) went through the column type and are equal by construction.
     */
    private static function matches(mixed $stored, int|string $requested, ?string $type): bool
    {
        if (!is_string($stored)) {
            return $stored !== null;
        }

        $requested = (string) $requested;
        if ($type === Types::GUID) {
            return strtolower($stored) === strtolower($requested) && preg_match('/\A[0-9a-f-]+\z/Di', $requested) === 1;
        }

        return $stored === $requested;
    }

    /**
     * @param ClassMetadata<object> $parentMetadata
     */
    private static function ownerField(ClassMetadata $parentMetadata, mixed $owner, string $field): mixed
    {
        return is_object($owner) ? $parentMetadata->getFieldValue($owner, $field) : null;
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
