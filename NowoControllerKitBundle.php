<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle;

use Nowo\ControllerKitBundle\DependencyInjection\ControllerKitExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle providing controller utilities: redirectToReferer and SafeForwardTrait.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class NowoControllerKitBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new ControllerKitExtension();
        }

        return $this->extension instanceof ExtensionInterface ? $this->extension : null;
    }
}
