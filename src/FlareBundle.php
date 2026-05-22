<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Flawe\FlareBundle\DependencyInjection\FlareExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FlareBundle extends AbstractBundle
{
    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FlareExtension();
    }
}
