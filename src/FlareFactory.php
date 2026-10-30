<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Monolog\Level;
use Psr\Log\LogLevel;
use Spatie\FlareClient\Flare;
use Spatie\FlareClient\FlareConfig;
use Spatie\FlareClient\Tracer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FlareFactory
{
    public const LOG_LEVELS = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY,
    ];
    private static Flare $flare;

    public static function init(ParameterBagInterface $parameterBag): Flare
    {
        if (isset(self::$flare)) {
            return self::$flare;
        }

        /**
         * @var array<string, mixed>&array{
         *   key: string,
         *   use_defaults: bool,
         * } $config
         */
        $config = $parameterBag->get('flare.config');

        $flareConfig = FlareConfig::make($config['key']);

        if ($config['use_defaults']) {
            $flareConfig->useDefaults();
        }

        self::setLog($flareConfig, $config);
        self::setReport($flareConfig, $config);
        self::setTrace($flareConfig, $config);
        self::setCollectConfig($flareConfig, $config);
        self::setCensorConfig($flareConfig, $config);
        self::setApplication($flareConfig, $config);

        self::$flare = Flare::make($flareConfig)->registerFlareHandlers();

        return self::$flare;
    }

    /**
     * @param array{
     *   log?: bool,
     *   minimal_log_level?: value-of<self::LOG_LEVELS>,
     * } $config
     */
    private static function setLog(FlareConfig $flareConfig, array $config): void
    {
        try {
            $logLevel = isset($config['minimal_log_level']) ? Level::fromName($config['minimal_log_level']) : null;
        }
        catch (\UnhandledMatchError $error) {
            $logLevel = null;
        }

        $flareConfig->log($config['log'] ?? false, $logLevel);
    }

    /**
     * @param array{
     *   report?: bool,
     *   report_error_levels?: int,
     * } $config
     */
    private static function setReport(FlareConfig $flareConfig, array $config): void
    {
        if (isset($config['report'])) {
            $flareConfig->report($config['report']);
        }

        if (isset($config['report_error_levels'])) {
            $flareConfig->reportErrorLevels($config['report_error_levels']);
        }
    }

    /**
     * @param array{
     *   trace?: bool,
     *   trace_limits?: array<string, int>,
     * } $config
     */
    private static function setTrace(FlareConfig $flareConfig, array $config): void
    {
        if (isset($config['trace'])) {
            $flareConfig->trace($config['trace']);
        }

        if (empty($config['trace_limits'])) {
            return;
        }

        $traceLimits = [
            'max_spans' => Tracer::DEFAULT_MAX_SPANS_LIMIT,
            'max_attributes_per_span' => Tracer::DEFAULT_MAX_ATTRIBUTES_PER_SPAN_LIMIT,
            'max_span_events_per_span' => Tracer::DEFAULT_MAX_SPAN_EVENTS_PER_SPAN_LIMIT,
            'max_attributes_per_span_event' => Tracer::DEFAULT_MAX_ATTRIBUTES_PER_SPAN_EVENT_LIMIT,
            ...$config['trace_limits'],
        ];

        $flareConfig->traceLimits(
            maxSpans: $traceLimits['max_spans'],
            maxAttributesPerSpan: $traceLimits['max_attributes_per_span'],
            maxSpanEventsPerSpan: $traceLimits['max_span_events_per_span'],
            maxAttributesPerSpanEvent: $traceLimits['max_attributes_per_span_event'],
        );
    }

    /**
     * @param array{
     *   collect?: array<string, bool>,
     * } $config
     */
    private static function setCollectConfig(FlareConfig $flareConfig, array $config): void
    {
        if (empty($config['collect'])) {
            return;
        }

        $collect = $config['collect'];

        if (isset($collect['errors_with_traces'])) {
            $collect['errors_with_traces'] ? $flareConfig->collectErrorsWithTraces() : $flareConfig->ignoreErrorsWithTraces();
        }

        if (isset($collect['dumps'])) {
            $collect['dumps'] ? $flareConfig->collectDumps() : $flareConfig->ignoreDumps();
        }

        if (isset($collect['context'])) {
            $collect['context'] ? $flareConfig->collectContext() : $flareConfig->ignoreContext();
        }

        if (isset($collect['commands'])) {
            $collect['commands'] ? $flareConfig->collectCommands() : $flareConfig->ignoreCommands();
        }

        if (isset($collect['requests'])) {
            $collect['requests'] ? $flareConfig->collectRequests() : $flareConfig->ignoreRequests();
        }

        if (isset($collect['cache_events'])) {
            $collect['cache_events'] ? $flareConfig->collectCacheEvents() : $flareConfig->ignoreCacheEvents();
        }

        if (isset($collect['logs_with_errors'])) {
            $collect['logs_with_errors'] ? $flareConfig->collectLogsWithErrors() : $flareConfig->ignoreLogsWithErrors();
        }

        if (isset($collect['queries'])) {
            $collect['queries'] ? $flareConfig->collectQueries() : $flareConfig->ignoreQueries();
        }

        if (isset($collect['transactions'])) {
            $collect['transactions'] ? $flareConfig->collectTransactions() : $flareConfig->ignoreTransactions();
        }

        if (isset($collect['external_http'])) {
            $collect['external_http'] ? $flareConfig->collectExternalHttp() : $flareConfig->ignoreExternalHttp();
        }

        if (isset($collect['filesystem_operations'])) {
            $collect['filesystem_operations'] ? $flareConfig->collectFilesystemOperations() : $flareConfig->ignoreFilesystemOperations();
        }

        if (isset($collect['git_info'])) {
            $collect['git_info'] ? $flareConfig->collectGitInfo() : $flareConfig->ignoreGitInfo();
        }

        if (isset($collect['views'])) {
            $collect['views'] ? $flareConfig->collectViews() : $flareConfig->ignoreViews();
        }

        if (isset($collect['glows'])) {
            $collect['glows'] ? $flareConfig->collectGlows() : $flareConfig->ignoreGlows();
        }

        if (isset($collect['stack_frame_arguments'])) {
            $collect['stack_frame_arguments'] ? $flareConfig->collectStackFrameArguments() : $flareConfig->ignoreStackFrameArguments();
        }

        if (isset($collect['server_info'])) {
            $collect['server_info'] ? $flareConfig->collectServerInfo() : $flareConfig->ignoreServerInfo();
        }
    }

    /**
     * @param array{
     *   censor?: array{
     *     client_ips?: bool,
     *     cookies?: bool,
     *     session?: bool,
     *     headers?: array<string>,
     *     body_fields?: array<string>,
     *   },
     * } $config
     */
    private static function setCensorConfig(FlareConfig $flareConfig, array $config): void
    {
        if (empty($config['censor'])) {
            return;
        }

        $censor = $config['censor'];

        if (isset($censor['client_ips'])) {
            $flareConfig->censorClientIps($censor['client_ips']);
        }

        if (isset($censor['cookies'])) {
            $flareConfig->censorCookies($censor['cookies']);
        }

        if (isset($censor['session'])) {
            $flareConfig->censorSession($censor['session']);
        }

        if (isset($censor['headers'])) {
            $flareConfig->censorHeaders(...$censor['headers']);
        }

        if (isset($censor['body_fields'])) {
            $flareConfig->censorBodyFields(...$censor['body_fields']);
        }
    }

    /**
     * @param array{
     *   application?: array{
     *     name?: string,
     *     version?: string,
     *     stage?: string,
     *     path?: string,
     *   },
     * } $config
     */
    private static function setApplication(FlareConfig $flareConfig, array $config): void
    {
        if (empty($config['application'])) {
            return;
        }

        $application = $config['application'];

        if (isset($application['name'])) {
            $flareConfig->applicationName($application['name']);
        }

        if (isset($application['version'])) {
            $flareConfig->applicationVersion($application['version']);
        }

        if (isset($application['stage'])) {
            $flareConfig->applicationStage($application['stage']);
        }

        if (isset($application['path'])) {
            $flareConfig->applicationPath($application['path']);
        }
    }
}
