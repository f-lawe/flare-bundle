<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Log;

use Monolog\Level as MonologLevel;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel as PsrLogLevel;
use Spatie\FlareClient\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * @internal
 */
final class FlareLogger extends AbstractLogger implements DebugLoggerInterface
{
    public function __construct(
        private readonly LoggerInterface $symfonyLogger,
        private readonly Logger $flareLogger,
    ) {}

    /**
     * @param mixed[] $context
     */
    #[\Override]
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $this->symfonyLogger->log($level, $message, $context);

        $monologLevel = match ($level) {
            PsrLogLevel::EMERGENCY => MonologLevel::Emergency,
            PsrLogLevel::ALERT     => MonologLevel::Alert,
            PsrLogLevel::CRITICAL  => MonologLevel::Critical,
            PsrLogLevel::ERROR     => MonologLevel::Error,
            PsrLogLevel::WARNING   => MonologLevel::Warning,
            PsrLogLevel::NOTICE    => MonologLevel::Notice,
            PsrLogLevel::INFO      => MonologLevel::Info,
            PsrLogLevel::DEBUG     => MonologLevel::Debug,
            default                => null,
        };

        if (null !== $monologLevel) {
            $this->flareLogger->record((string) $message, $monologLevel, $context);
        }
    }

    #[\Override]
    public function getLogs(?Request $request = null): array
    {
        return $this->symfonyLogger instanceof DebugLoggerInterface ? $this->symfonyLogger->getLogs($request) : [];
    }

    #[\Override]
    public function countErrors(?Request $request = null): int
    {
        return $this->symfonyLogger instanceof DebugLoggerInterface ? $this->symfonyLogger->countErrors($request) : 0;
    }

    #[\Override]
    public function clear(): void
    {
        if ($this->symfonyLogger instanceof DebugLoggerInterface) {
            $this->symfonyLogger->clear();
        }
    }
}
