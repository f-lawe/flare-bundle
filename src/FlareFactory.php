<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Spatie\FlareClient\Flare;
use Spatie\FlareClient\FlareConfig;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FlareFactory
{
    private static Flare $flare;

    public static function init(ParameterBagInterface $parameterBag): Flare
    {
        if (isset(self::$flare)) {
            return self::$flare;
        }

        /**
         * @var array{
         *   key: string,
         *   use_defaults: bool,
         *   trace?: bool,
         *   collect?: array<string, bool>,
         *   censor?: array<string, array<int, string>|bool>,
         * } $config
         */
        $config = $parameterBag->get('flare.config');

        $flareConfig = FlareConfig::make($config['key']);

        if ($config['use_defaults']) {
            $flareConfig->useDefaults();
        }

        if (isset($config['trace'])) {
            $flareConfig->trace($config['trace']);
        }

        if (isset($config['collect'])) {
            if (isset($config['collect']['errors_with_traces'])) {
                $config['collect']['errors_with_traces'] ? $flareConfig->collectErrorsWithTraces() : $flareConfig->ignoreErrorsWithTraces();
            }

            if (isset($config['collect']['dumps'])) {
                $config['collect']['dumps'] ? $flareConfig->collectDumps() : $flareConfig->ignoreDumps();
            }

            if (isset($config['collect']['context'])) {
                $config['collect']['context'] ? $flareConfig->collectContext() : $flareConfig->ignoreContext();
            }

            if (isset($config['collect']['commands'])) {
                $config['collect']['commands'] ? $flareConfig->collectCommands() : $flareConfig->ignoreCommands();
            }

            if (isset($config['collect']['requests'])) {
                $config['collect']['requests'] ? $flareConfig->collectRequests() : $flareConfig->ignoreRequests();
            }

            if (isset($config['collect']['cache_events'])) {
                $config['collect']['cache_events'] ? $flareConfig->collectCacheEvents() : $flareConfig->ignoreCacheEvents();
            }

            if (isset($config['collect']['logs_with_errors'])) {
                $config['collect']['logs_with_errors'] ? $flareConfig->collectLogsWithErrors() : $flareConfig->ignoreLogsWithErrors();
            }

            if (isset($config['collect']['queries'])) {
                $config['collect']['queries'] ? $flareConfig->collectQueries() : $flareConfig->ignoreQueries();
            }

            if (isset($config['collect']['transactions'])) {
                $config['collect']['transactions'] ? $flareConfig->collectTransactions() : $flareConfig->ignoreTransactions();
            }

            if (isset($config['collect']['external_http'])) {
                $config['collect']['external_http'] ? $flareConfig->collectExternalHttp() : $flareConfig->ignoreExternalHttp();
            }

            if (isset($config['collect']['filesystem_operations'])) {
                $config['collect']['filesystem_operations'] ? $flareConfig->collectFilesystemOperations() : $flareConfig->ignoreFilesystemOperations();
            }

            if (isset($config['collect']['git_info'])) {
                $config['collect']['git_info'] ? $flareConfig->collectGitInfo() : $flareConfig->ignoreGitInfo();
            }

            if (isset($config['collect']['views'])) {
                $config['collect']['views'] ? $flareConfig->collectViews() : $flareConfig->ignoreViews();
            }

            if (isset($config['collect']['glows'])) {
                $config['collect']['glows'] ? $flareConfig->collectGlows() : $flareConfig->ignoreGlows();
            }

            if (isset($config['collect']['stack_frame_arguments'])) {
                $config['collect']['stack_frame_arguments'] ? $flareConfig->collectStackFrameArguments() : $flareConfig->ignoreStackFrameArguments();
            }

            if (isset($config['collect']['server_info'])) {
                $config['collect']['server_info'] ? $flareConfig->collectServerInfo() : $flareConfig->ignoreServerInfo();
            }
        }

        if (isset($config['censor'])) {
            if (isset($config['censor']['client_ips']) && \is_bool($config['censor']['client_ips'])) {
                $flareConfig->censorClientIps($config['censor']['client_ips']);
            }

            if (isset($config['censor']['cookies']) && \is_bool($config['censor']['cookies'])) {
                $flareConfig->censorCookies($config['censor']['cookies']);
            }

            if (isset($config['censor']['session']) && \is_bool($config['censor']['session'])) {
                $flareConfig->censorSession($config['censor']['session']);
            }

            if (isset($config['censor']['headers']) && \is_array($config['censor']['headers'])) {
                $flareConfig->censorHeaders(...$config['censor']['headers']);
            }

            if (isset($config['censor']['body_fields']) && \is_array($config['censor']['body_fields'])) {
                $flareConfig->censorBodyFields(...$config['censor']['body_fields']);
            }
        }

        self::$flare = Flare::make($flareConfig)->registerFlareHandlers();

        return self::$flare;
    }
}
