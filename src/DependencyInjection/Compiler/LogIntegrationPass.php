<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\DependencyInjection\Compiler;

use Flawe\FlareBundle\Logger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class LogIntegrationPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasExtension('monolog')) {
            $container->removeDefinition(Logger::class);
        }
    }
}
