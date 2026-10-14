<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle;

use Nowo\ControllerKitBundle\DependencyInjection\Compiler\ScopedEntityValueResolverPass;
use Nowo\ControllerKitBundle\DependencyInjection\ControllerKitExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle providing controller utilities: redirectToReferer, SafeForwardTrait, RequiresValidFormTrait
 * and the #[MapScopedEntity] owner-scoped entity argument resolver.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class NowoControllerKitBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ScopedEntityValueResolverPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, ScopedEntityValueResolverPass::PRIORITY);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            // @igor-ignore - Boot-time Symfony Bundle extension cache (not request state).
            $this->extension = new ControllerKitExtension();
        }

        return $this->extension instanceof ExtensionInterface ? $this->extension : null;
    }
}
