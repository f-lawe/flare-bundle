<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Spatie\FlareClient\Flare;
use Spatie\FlareClient\FlareConfig;

class FlareFactory
{
    private static Flare $flare;

    /**
     * @param array{
     *   key: string,
     *   trace?: bool,
     *   censor?: array{
     *     client_ips?: bool,
     *     body_fields?: array<int, string>,
     *     headers?: array<int, string>,
     *     cookies?: bool,
     *     session?: bool
     * }} $config
     */
    public static function createFlare(array $config): void
    {
        $flareConfig = FlareConfig::make($config['key'])->useDefaults();

        if (\array_key_exists('trace', $config)) {
            $flareConfig->trace($config['trace']);
        }

        if (\array_key_exists('censor', $config)) {
            if (\array_key_exists('client_ips', $config['censor'])) {
                $flareConfig->censorClientIps($config['censor']['client_ips']);
            }

            if (\array_key_exists('body_fields', $config['censor'])) {
                $flareConfig->censorBodyFields(...$config['censor']['body_fields']);
            }

            if (\array_key_exists('headers', $config['censor'])) {
                $flareConfig->censorHeaders(...$config['censor']['headers']);
            }

            if (\array_key_exists('cookies', $config['censor'])) {
                $flareConfig->censorCookies($config['censor']['cookies']);
            }

            if (\array_key_exists('session', $config['censor'])) {
                $flareConfig->censorSession($config['censor']['session']);
            }
        }

        self::$flare = Flare::make($flareConfig)->registerFlareHandlers();
    }

    public static function loadFlare(): Flare
    {
        return self::$flare;
    }
}
