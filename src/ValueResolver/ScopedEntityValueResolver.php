<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\ValueResolver;

use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Nowo\ControllerKitBundle\Attribute\MapScopedEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function get_debug_type;
use function is_object;
use function sprintf;

/**
 * Resolves {@see MapScopedEntity} arguments scoped to the route owner (spec 003): a missing entity, an entity of
 * another owner and a malformed identifier are the same 404 (or `null` for a nullable argument).
 *
 * For each argument the tagged {@see ScopedEntityFinderInterface} finders (2.4+) are asked in priority order; the
 * first that supports the class loads it. Otherwise the built-in {@see DoctrineScopedEntityFinder} runs one Doctrine
 * ORM query, joined on the owner association or (2.4+) matched on a plain owner field.
 *
 * Registered as a targeted value resolver when doctrine/orm and the `doctrine` registry (DoctrineBundle) are
 * available, or when at least one finder is tagged. Stateless and FrankenPHP worker-safe.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class ScopedEntityValueResolver implements ValueResolverInterface
{
    private ?DoctrineScopedEntityFinder $doctrine;

    /**
     * @param ManagerRegistry|null $registry Doctrine registry for the built-in query (null without Doctrine)
     * @param iterable<ScopedEntityFinderInterface> $finders application finders, highest priority first (2.4+)
     */
    public function __construct(
        ?ManagerRegistry $registry,
        private iterable $finders = [],
    ) {
        $this->doctrine = $registry instanceof ManagerRegistry ? new DoctrineScopedEntityFinder($registry) : null;
    }

    /**
     * Resolves a `#[MapScopedEntity]` argument with one owner-scoped lookup.
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

        $finder = null;
        foreach ($this->finders as $candidate) {
            if ($candidate->supports($class, $attribute)) {
                $finder = $candidate;
                break;
            }
        }

        $id       = $request->attributes->get($attribute->id);
        $parentId = $request->attributes->get($attribute->getParentRouteAttribute());

        if ($finder instanceof ScopedEntityFinderInterface) {
            $entity = DoctrineScopedEntityFinder::isIdentifier($id) && DoctrineScopedEntityFinder::isIdentifier($parentId)
                ? $finder->findScoped($class, $id, $parentId, $attribute)
                : null;
        } elseif ($this->doctrine instanceof DoctrineScopedEntityFinder) {
            // Mapping errors surface before any route value is read, as in 2.3.
            $finder = $this->doctrine;
            $entity = $finder->findForRoute($class, $id, $parentId, $attribute, $argument->getName());
        } else {
            throw new LogicException(sprintf('#[MapScopedEntity] needs Doctrine ORM or a %s that supports "%s" ("$%s").', ScopedEntityFinderInterface::class, $class, $argument->getName()));
        }

        if (is_object($entity) && !$entity instanceof $class) {
            throw new LogicException(sprintf('#[MapScopedEntity] finder "%s" returned "%s", expected "%s" ("$%s").', get_debug_type($finder), get_debug_type($entity), $class, $argument->getName()));
        }

        if (!is_object($entity) && !$argument->isNullable()) {
            throw new NotFoundHttpException(sprintf('"%s" object not found by "%s".', $class, self::class));
        }

        return [is_object($entity) ? $entity : null];
    }
}
