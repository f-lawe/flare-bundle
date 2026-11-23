<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Support;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Spatie\FlareClient\Logger;
use Spatie\FlareClient\Support\FlareLogHandler as SpatieFlareLogHandler;

/**
 * @internal
 */
final class FlareLogHandler extends SpatieFlareLogHandler
{
    private readonly PsrLogMessageProcessor $messageProcessor;

    public function __construct(
        Logger $logger,
        int|Level|string $level = SpatieFlareLogHandler::DEFAULT_MONOLOG_LEVEL,
        bool $bubble = true,
    ) {
        parent::__construct($logger, $level, $bubble);

        $this->messageProcessor = new PsrLogMessageProcessor();
    }

    #[\Override]
    protected function write(LogRecord $record): void
    {
        parent::write(($this->messageProcessor)($record));
    }
}
