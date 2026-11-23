<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Flawe\FlareBundle\Support\DryRunContainer;
use Spatie\FlareClient\Enums\SpanType;
use Spatie\FlareClient\FlareProvider;
use Spatie\FlareClient\Spans\Span;
use Spatie\FlareClient\Support\Container as FlareClientContainer;

/**
 * @internal
 */
final class FlareFactory
{
    private const SERVER_FRANKENPHP_WORKER = 'FRANKENPHP_WORKER';
    private const SERVER_RR_MODE           = 'RR_MODE';
    private const SERVER_ARGV              = 'argv';

    private const LONG_RUNNING_COMMANDS = [
        'messenger:consume',
    ];

    private const SPANS_CLOSED_BY_BUNDLE = [
        SpanType::Application,
        SpanType::ApplicationTerminating,
        SpanType::Request,
        SpanType::Command,
        SpanType::Job,
        SpanType::QueueingJob,
    ];

    private const INI_SETTINGS_RESTORED_AFTER_DRY_RUN = [
        'zend.exception_ignore_args',
    ];

    /**
     * @param array<string, mixed> $config
     *
     * @return array<class-string, bool> Service ids, mapped to whether they are shared
     */
    public static function discoverServices(array $config): array
    {
        $container = new DryRunContainer();

        $iniSettings = [];

        foreach (self::INI_SETTINGS_RESTORED_AFTER_DRY_RUN as $name) {
            $iniSettings[$name] = \ini_get($name);
        }

        try {
            self::createProvider($config, $container)->register();
        }
        finally {
            foreach ($iniSettings as $name => $value) {
                if ($value !== false) {
                    ini_set($name, $value);
                }
            }
        }

        return $container->services();
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function createProvider(array $config, FlareClientContainer $container): FlareProvider
    {
        $flareConfig = FlareConfig::fromSymfonyConfig($config);

        return new FlareProvider(
            config: $flareConfig,
            container: $container,
            collectsResolver: $flareConfig->collectsResolver,
            isUsingSubtasksClosure: match ($config['subtasks'] ?? null) {
                'always' => static fn (): bool => true,
                'never'  => static fn (): bool => false,
                default  => self::isLongRunningProcess(...),
            },
            gracefulSpanEnderClosure: self::isEndedGracefully(...),
            disableApiQueue: ($config['disable_api_queue'] ?? false) === true,
        );
    }

    private static function isLongRunningProcess(): bool
    {
        if ($_SERVER[self::SERVER_FRANKENPHP_WORKER] ?? false) {
            return true;
        }

        if (($_SERVER[self::SERVER_RR_MODE] ?? getenv(self::SERVER_RR_MODE)) !== false) {
            return true;
        }

        $argv = $_SERVER[self::SERVER_ARGV] ?? null;

        if (\PHP_SAPI !== 'cli' || !\is_array($argv)) {
            return false;
        }

        foreach (self::LONG_RUNNING_COMMANDS as $command) {
            if (\in_array($command, $argv, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Spans closed by the bundle's subscribers must not be ended gracefully by Reporter::report().
     */
    private static function isEndedGracefully(Span $span): bool
    {
        $type = $span->attributes['flare.span_type'] ?? null;

        if (\is_string($type)) {
            $type = SpanType::tryFrom($type);
        }

        return !\in_array($type, self::SPANS_CLOSED_BY_BUNDLE, true);
    }
}
