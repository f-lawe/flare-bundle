<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Flawe\FlareBundle\DependencyInjection\Compiler\LogIntegrationPass;
use Flawe\FlareBundle\Log\FlareLogger;
use Flawe\FlareBundle\Support\RuntimeContainer;
use Spatie\FlareClient\Flare;
use Spatie\FlareClient\Logger;
use Spatie\FlareClient\Support\FlareLogHandler;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service_locator;

class FlareBundle extends AbstractBundle
{
    private const SERVER_HTTP_TRACEPARENT = 'HTTP_TRACEPARENT';

    #[\Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    #[\Override]
    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        if (!$container->hasExtension('monolog') || !$this->isAutoRegisterEnabled($container)) {
            return;
        }

        // Registered even when logs are disabled: Flare's logger then ignores the records.
        $configurator->extension('monolog', [
            'handlers' => [
                'flare' => [
                    'type' => 'service',
                    'id'   => FlareLogHandler::class,
                ],
            ],
        ], prepend: true);
    }

    /**
     * @param array<string, mixed> $config
     */
    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.php');

        $services = $configurator->services();

        self::loadFlareServices($config, $services);
        self::loadLoggers($config, $services);
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new LogIntegrationPass());
    }

    #[\Override]
    public function boot(): void
    {
        $flare = $this->container?->get('flare');

        if (!$flare instanceof Flare) {
            return;
        }

        $traceparent = $_SERVER[self::SERVER_HTTP_TRACEPARENT] ?? null;

        // Both are no-ops in subtask mode, where subscribers drive one subtask per unit of work.
        $flare->lifecycle->start(traceparent: \is_string($traceparent) ? $traceparent : null);
        $flare->lifecycle->boot();

        if (!self::hasSymfonyErrorHandler()) {
            $flare->registerExceptionHandler();
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function loadFlareServices(array $config, ServicesConfigurator $services): void
    {
        $locator = [];

        foreach (FlareFactory::discoverServices($config) as $id => $shared) {
            $services->set($id)
                ->factory([service(RuntimeContainer::class), 'build'])
                ->args([$id])
                ->share($shared)
            ;

            $locator[$id] = service($id);
        }

        $services->get(Flare::class)->configurator([service(RuntimeContainer::class), 'bootProvider']);

        $services->get(RuntimeContainer::class)->args([
            '$config'   => $config,
            '$services' => service_locator($locator),
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function loadLoggers(array $config, ServicesConfigurator $services): void
    {
        $log = \is_array($config['log'] ?? null) ? $config['log'] : [];

        if (($log['auto_register'] ?? true) === false) {
            return;
        }

        $services->set(FlareLogHandler::class)
            ->arg('$logger', service(Logger::class))
        ;

        // Removed by LogIntegrationPass when MonologBundle is enabled.
        $services->set(FlareLogger::class)
            ->decorate('logger', null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->args([
                '$symfonyLogger' => service('.inner'),
                '$flareLogger'   => service(Logger::class),
            ])
        ;
    }

    private function isAutoRegisterEnabled(ContainerBuilder $container): bool
    {
        $autoRegister = true;

        foreach ($container->getExtensionConfig($this->extensionAlias) as $config) {
            if (\is_array($config['log'] ?? null) && \array_key_exists('auto_register', $config['log'])) {
                $autoRegister = $config['log']['auto_register'];
            }
        }

        return $autoRegister !== false;
    }

    private static function hasSymfonyErrorHandler(): bool
    {
        if (\function_exists('get_exception_handler')) {
            // PHP >= 8.5
            $handler = get_exception_handler();
        }
        else {
            $handler = set_exception_handler(null);
            restore_exception_handler();
        }

        return \is_array($handler) && $handler[0] instanceof ErrorHandler;
    }
}
