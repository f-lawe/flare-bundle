<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\DependencyInjection;

use Flawe\FlareBundle\Log\FlareLogger;
use Spatie\FlareClient\Logger;
use Spatie\FlareClient\Support\FlareLogHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class FlareExtension extends Extension implements PrependExtensionInterface
{
    private const string MONOLOG_ENABLED_PARAMETER = '.flare.monolog_enabled';

    #[\Override]
    public function prepend(ContainerBuilder $container): void
    {
        // Other extensions are only visible here: load() receives a container holding this extension only.
        $hasMonolog = $container->hasExtension('monolog');
        $container->setParameter(self::MONOLOG_ENABLED_PARAMETER, $hasMonolog);

        if (!$hasMonolog) {
            return;
        }

        // Configs are not processed yet (and may contain env placeholders): merge the raw configs.
        // Lists are merged by index, unlike Symfony which appends them: only rely on scalar options here.
        $config = array_replace_recursive([], ...$container->getExtensionConfig($this->getAlias()));

        if (!self::isLogIntegrationEnabled($config)) {
            return;
        }

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
        $container->setParameter('flare.config', $config);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        if (self::isLogIntegrationEnabled($config)) {
            $isMonologEnabled = $container->hasParameter(self::MONOLOG_ENABLED_PARAMETER)
                && true === $container->getParameter(self::MONOLOG_ENABLED_PARAMETER);

            if ($isMonologEnabled) {
                $container->register(FlareLogHandler::class, FlareLogHandler::class)
                    ->setArgument('$logger', new Reference(Logger::class))
                ;
            }
            else {
                $container->register(FlareLogger::class, FlareLogger::class)
                    ->setDecoratedService('logger', null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
                    ->setArguments([
                        '$symfonyLogger' => new Reference('.inner'),
                        '$flareLogger'   => new Reference(Logger::class),
                    ])
                ;
            }
        }
    }

    /**
     * @param array<mixed> $config
     */
    private static function isLogIntegrationEnabled(array $config): bool
    {
        $log    = \is_array($config['log'] ?? null) ? $config['log'] : [];
        $enable = $log['enable'] ?? null;

        return null !== $enable && false !== $enable && false !== ($log['auto_register'] ?? true);
    }
}
