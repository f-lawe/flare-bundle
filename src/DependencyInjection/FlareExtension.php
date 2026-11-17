<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\DependencyInjection;

use Flawe\FlareBundle\Log\FlareLogger;
use Flawe\FlareBundle\Support\Container;
use Spatie\FlareClient\Logger;
use Spatie\FlareClient\Support\FlareLogHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class FlareExtension extends Extension implements PrependExtensionInterface
{
    #[\Override]
    public function prepend(ContainerBuilder $container): void
    {
        // Other extensions are only visible in prepend(): load() receives a container holding this extension only.
        $configs = $container->getExtensionConfig($this->getAlias());

        if (!$container->hasExtension('monolog') || !self::isAutoRegisterEnabled($configs)) {
            return;
        }

        // Registered even when logs are disabled: Flare's logger then ignores the records.
        $container->prependExtensionConfig('monolog', [
            'handlers' => [
                'flare' => [
                    'type' => 'service',
                    'id'   => FlareLogHandler::class,
                ],
            ],
        ]);
    }

    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->getDefinition(Container::class)->setArgument('$config', $config);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        if (false === self::isAutoRegisterEnabled([$config])) {
            return;
        }

        $container->register(FlareLogHandler::class, FlareLogHandler::class)
            ->setArgument('$logger', new Reference(Logger::class))
        ;

        // Removed by LogIntegrationPass when MonologBundle is enabled.
        $container->register(FlareLogger::class, FlareLogger::class)
            ->setDecoratedService('logger', null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->setArguments([
                '$symfonyLogger' => new Reference('.inner'),
                '$flareLogger'   => new Reference(Logger::class),
            ])
        ;
    }

    /**
     * @param array<mixed> $configs
     */
    private static function isAutoRegisterEnabled(array $configs): bool
    {
        $autoRegister = true;

        foreach ($configs as $config) {
            $autoRegister = \is_array($config) && \is_array($config['log'] ?? null) ? true === $config['log']['auto_register'] : true;
        }

        return $autoRegister;
    }
}
