<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\ValueResolver;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
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
 * Resolves {@see MapScopedEntity} arguments with one Doctrine ORM query scoped to the owner
 * (spec 003): a missing entity, an entity of another owner and a malformed identifier are the same
 * 404 (or `null` for a nullable argument).
 *
 * The owner (`parent`) is either an association, joined in the query, or (2.4+) a plain mapped
 * field holding the owner id, compared directly. When a {@see ScopedEntityFinderInterface} is given
 * (2.4+), every argument is delegated to it instead and Doctrine is not used.
 *
 * Registered as a targeted value resolver when doctrine/orm and the `doctrine` registry
 * (DoctrineBundle) are available, or when the application registers a
 * {@see ScopedEntityFinderInterface} service. Stateless and FrankenPHP worker-safe.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class ScopedEntityValueResolver implements ValueResolverInterface
{
    /**
     * @param ManagerRegistry|null $registry Doctrine registry used to pick the entity manager (null without Doctrine)
     * @param ScopedEntityFinderInterface|null $finder application finder that replaces the Doctrine query (2.4+)
     */
    public function __construct(
        private ?ManagerRegistry $registry,
        private ?ScopedEntityFinderInterface $finder = null,
    ) {
    }

    /**
     * Resolves a `#[MapScopedEntity]` argument with one owner-scoped query.
     *
     * @param Request $request Current request (route attributes hold both identifiers)
     * @param ArgumentMetadata $argument Controller argument metadata
     *
     * @return iterable<object|null> The entity, `null` for a nullable argument, or nothing when the attribute is absent
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

        if ($this->finder instanceof ScopedEntityFinderInterface) {
            $id       = $request->attributes->get($attribute->id);
            $parentId = $request->attributes->get($attribute->getParentRouteAttribute());
            $entity   = $this->isIdentifier($id) && $this->isIdentifier($parentId)
                ? $this->finder->findScoped($class, $id, $parentId, $attribute)
                : null;

            return $this->answer($entity, $class, $argument);
        }

        if (!$this->registry instanceof ManagerRegistry) {
            throw new LogicException(sprintf('#[MapScopedEntity] needs Doctrine ORM or a %s service ("$%s").', ScopedEntityFinderInterface::class, $argument->getName()));
        }

        $manager = $attribute->objectManager === null
            ? $this->registry->getManagerForClass($class)
            : $this->registry->getManager($attribute->objectManager);
        if (!$manager instanceof EntityManagerInterface) {
            throw new LogicException(sprintf('#[MapScopedEntity] needs a Doctrine ORM entity manager for "%s" ("$%s").', $class, $argument->getName()));
        }

        $metadata = $manager->getClassMetadata($class);
        // Owner by association (joined) or, since 2.4, by a plain field holding the owner id (compared directly).
        $ownerIsField = !$metadata->hasAssociation($attribute->parent) && $metadata->hasField($attribute->parent);
        if (!$ownerIsField && !$metadata->hasAssociation($attribute->parent)) {
            throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no association "%s" ("$%s").', $class, $attribute->parent, $argument->getName()));
        }

        if (!$metadata->hasField($attribute->field)) {
            throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no field "%s" ("$%s").', $class, $attribute->field, $argument->getName()));
        }

        $parentMetadata = null;
        if (!$ownerIsField) {
            $parentMetadata = $manager->getClassMetadata($metadata->getAssociationTargetClass($attribute->parent));
            if (!$parentMetadata->hasField($attribute->parentField)) {
                throw new LogicException(sprintf('#[MapScopedEntity] "%s" has no field "%s" ("$%s").', $parentMetadata->getName(), $attribute->parentField, $argument->getName()));
            }
        }

        $id       = $request->attributes->get($attribute->id);
        $parentId = $request->attributes->get($attribute->getParentRouteAttribute());

        $entity = null;
        if ($this->isIdentifier($id) && $this->isIdentifier($parentId)) {
            try {
                $query = $manager->createQueryBuilder()
                    ->select('e')
                    ->from($class, 'e')
                    ->andWhere(sprintf('e.%s = :id', $attribute->field))
                    ->setParameter('id', $id, $metadata->getTypeOfField($attribute->field));
                if (!$parentMetadata instanceof ClassMetadata) {
                    $query
                        ->andWhere(sprintf('e.%s = :parentId', $attribute->parent))
                        ->setParameter('parentId', $parentId, $metadata->getTypeOfField($attribute->parent));
                } else {
                    $query
                        ->innerJoin('e.' . $attribute->parent, 'p')
                        ->andWhere(sprintf('p.%s = :parentId', $attribute->parentField))
                        ->setParameter('parentId', $parentId, $parentMetadata->getTypeOfField($attribute->parentField));
                }

                $entity = $query->getQuery()->getOneOrNullResult();
            } catch (ConversionException) {
                // Malformed identifier for the column type (e.g. not a UUID): same answer as "not found".
                $entity = null;
            }
        }

        return $this->answer($entity, $class, $argument);
    }

    /**
     * @return array{object|null}
     */
    private function answer(mixed $entity, string $class, ArgumentMetadata $argument): array
    {
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
