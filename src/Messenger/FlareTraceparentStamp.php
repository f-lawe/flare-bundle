<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Carries the trace of the code dispatching a message, continued by the worker handling it.
 */
final readonly class FlareTraceparentStamp implements StampInterface
{
    public function __construct(
        public string $traceparent,
    ) {}
}
