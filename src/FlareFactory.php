<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Flawe\FlareBundle\Support\Container;
use Psr\Container\ContainerInterface;
use Spatie\FlareClient\Enums\SpanType;
use Spatie\FlareClient\FlareProvider;
use Spatie\FlareClient\Spans\Span;

class FlareFactory
{
    private const SERVER_FRANKENPHP_WORKER  = 'FRANKENPHP_WORKER';
    private const SERVER_RR_MODE            = 'RR_MODE';
    private const SERVER_ARGV               = 'argv';
    private const MESSENGER_CONSUME_COMMAND = 'messenger:consume';

    private const SPANS_CLOSED_BY_BUNDLE = [
        SpanType::Application,
        SpanType::ApplicationTerminating,
        SpanType::Request,
        SpanType::Command,
        SpanType::Job,
    ];

    /**
     * @param array<string, mixed>&array{
     *   subtasks: 'always'|'auto'|'never',
     *   disable_api_queue: bool,
     * } $config
     */
    public static function create(array $config, ContainerInterface $overrides): Container
    {
        $container   = new Container($overrides);
        $flareConfig = FlareConfig::fromSymfonyConfig($config);

        $isUsingSubtasksClosure = static fn (): bool => match ($config['subtasks']) {
            'always' => true,
            'never'  => false,
            'auto'   => self::isLongRunningProcess(),
        };
        $gracefulSpanEnderClosure = static fn (Span $span): bool => !self::isClosedByBundle($span);

        $provider = new FlareProvider(
            config: $flareConfig,
            container: $container,
            collectsResolver: $flareConfig->collectsResolver,
            isUsingSubtasksClosure: $isUsingSubtasksClosure,
            gracefulSpanEnderClosure: $gracefulSpanEnderClosure,
            disableApiQueue: $config['disable_api_queue'],
        );
        $provider->register();
        $provider->boot();

        return $container;
    }

    private static function isLongRunningProcess(): bool
    {
        if ($_SERVER[self::SERVER_FRANKENPHP_WORKER] ?? false) {
            return true;
        }

        if (($_SERVER[self::SERVER_RR_MODE] ?? getenv(self::SERVER_RR_MODE)) !== false) {
            return true;
        }

        $argv = $_SERVER[self::SERVER_ARGV] ?? [];

        return \PHP_SAPI === 'cli' && \is_array($argv) && \in_array(self::MESSENGER_CONSUME_COMMAND, $argv, true);
    }

    /**
     * Checks if span is by the bundle's kernel/console subscribers (ie. not by Reporter::report()).
     */
    private static function isClosedByBundle(Span $span): bool
    {
        $type = $span->attributes['flare.span_type'] ?? null;

        if (\is_string($type)) {
            $type = SpanType::tryFrom($type);
        }

        return \in_array($type, self::SPANS_CLOSED_BY_BUNDLE, true);
    }
}
