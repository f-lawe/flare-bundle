<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Monolog\Level;
use Psr\Log\LogLevel;
use Spatie\FlareClient\Tracer;

class FlareConfig extends \Spatie\FlareClient\FlareConfig
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

    /**
     * @param array<string, mixed> $config
     */
    public static function fromSymfonyConfig(array $config): self
    {
        /**
         * @var array<string, mixed>&array{
         *   key: ?string,
         *   use_defaults: bool,
         * } $config
         */
        $flareConfig = self::make(trim((string) $config['key']));

        if ($config['use_defaults']) {
            $flareConfig->useDefaults();
        }

        $flareConfig->setLog($config);
        $flareConfig->setReport($config);
        $flareConfig->setTrace($config);
        $flareConfig->setCollectConfig($config);
        $flareConfig->setCensorConfig($config);
        $flareConfig->setApplication($config);

        return $flareConfig;
    }

    /**
     * @param array{
     *   log?: bool,
     *   minimal_log_level?: value-of<self::LOG_LEVELS>,
     * } $config
     */
    private function setLog(array $config): void
    {
        try {
            $logLevel = isset($config['minimal_log_level']) ? Level::fromName($config['minimal_log_level']) : null;
        }
        catch (\UnhandledMatchError $error) {
            $logLevel = null;
        }

        $this->log($config['log'] ?? false, $logLevel);
    }

    /**
     * @param array{
     *   report?: bool,
     *   report_error_levels?: int,
     * } $config
     */
    private function setReport(array $config): void
    {
        if (isset($config['report'])) {
            $this->report($config['report']);
        }

        if (isset($config['report_error_levels'])) {
            $this->reportErrorLevels($config['report_error_levels']);
        }
    }

    /**
     * @param array{
     *   trace?: bool,
     *   trace_limits?: array<string, int>,
     * } $config
     */
    private function setTrace(array $config): void
    {
        if (isset($config['trace'])) {
            $this->trace($config['trace']);
        }

        if (empty($config['trace_limits'])) {
            return;
        }

        $traceLimits = [
            'max_spans'                     => Tracer::DEFAULT_MAX_SPANS_LIMIT,
            'max_attributes_per_span'       => Tracer::DEFAULT_MAX_ATTRIBUTES_PER_SPAN_LIMIT,
            'max_span_events_per_span'      => Tracer::DEFAULT_MAX_SPAN_EVENTS_PER_SPAN_LIMIT,
            'max_attributes_per_span_event' => Tracer::DEFAULT_MAX_ATTRIBUTES_PER_SPAN_EVENT_LIMIT,
            ...$config['trace_limits'],
        ];

        $this->traceLimits(
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
    private function setCollectConfig(array $config): void
    {
        if (empty($config['collect'])) {
            return;
        }

        $collect = $config['collect'];

        if (isset($collect['errors_with_traces'])) {
            $collect['errors_with_traces'] ? $this->collectErrorsWithTraces() : $this->ignoreErrorsWithTraces();
        }

        if (isset($collect['dumps'])) {
            $collect['dumps'] ? $this->collectDumps() : $this->ignoreDumps();
        }

        if (isset($collect['context'])) {
            $collect['context'] ? $this->collectContext() : $this->ignoreContext();
        }

        if (isset($collect['commands'])) {
            $collect['commands'] ? $this->collectCommands() : $this->ignoreCommands();
        }

        if (isset($collect['requests'])) {
            $collect['requests'] ? $this->collectRequests() : $this->ignoreRequests();
        }

        if (isset($collect['cache_events'])) {
            $collect['cache_events'] ? $this->collectCacheEvents() : $this->ignoreCacheEvents();
        }

        if (isset($collect['logs_with_errors'])) {
            $collect['logs_with_errors'] ? $this->collectLogsWithErrors() : $this->ignoreLogsWithErrors();
        }

        if (isset($collect['queries'])) {
            $collect['queries'] ? $this->collectQueries() : $this->ignoreQueries();
        }

        if (isset($collect['transactions'])) {
            $collect['transactions'] ? $this->collectTransactions() : $this->ignoreTransactions();
        }

        if (isset($collect['external_http'])) {
            $collect['external_http'] ? $this->collectExternalHttp() : $this->ignoreExternalHttp();
        }

        if (isset($collect['filesystem_operations'])) {
            $collect['filesystem_operations'] ? $this->collectFilesystemOperations() : $this->ignoreFilesystemOperations();
        }

        if (isset($collect['git_info'])) {
            $collect['git_info'] ? $this->collectGitInfo() : $this->ignoreGitInfo();
        }

        if (isset($collect['views'])) {
            $collect['views'] ? $this->collectViews() : $this->ignoreViews();
        }

        if (isset($collect['glows'])) {
            $collect['glows'] ? $this->collectGlows() : $this->ignoreGlows();
        }

        if (isset($collect['stack_frame_arguments'])) {
            $collect['stack_frame_arguments'] ? $this->collectStackFrameArguments() : $this->ignoreStackFrameArguments();
        }

        if (isset($collect['server_info'])) {
            $collect['server_info'] ? $this->collectServerInfo() : $this->ignoreServerInfo();
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
    private function setCensorConfig(array $config): void
    {
        if (empty($config['censor'])) {
            return;
        }

        $censor = $config['censor'];

        if (isset($censor['client_ips'])) {
            $this->censorClientIps($censor['client_ips']);
        }

        if (isset($censor['cookies'])) {
            $this->censorCookies($censor['cookies']);
        }

        if (isset($censor['session'])) {
            $this->censorSession($censor['session']);
        }

        if (isset($censor['headers'])) {
            $this->censorHeaders(...$censor['headers']);
        }

        if (isset($censor['body_fields'])) {
            $this->censorBodyFields(...$censor['body_fields']);
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
    private function setApplication(array $config): void
    {
        if (empty($config['application'])) {
            return;
        }

        $application = $config['application'];

        if (isset($application['name'])) {
            $this->applicationName($application['name']);
        }

        if (isset($application['version'])) {
            $this->applicationVersion($application['version']);
        }

        if (isset($application['stage'])) {
            $this->applicationStage($application['stage']);
        }

        if (isset($application['path'])) {
            $this->applicationPath($application['path']);
        }
    }
}
