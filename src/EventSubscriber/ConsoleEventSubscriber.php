<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\EventSubscriber;

use Spatie\FlareClient\Flare;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Flare $flare
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 4096],
            ConsoleEvents::ERROR   => ['onConsoleError', 0],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        // Listening is enough: instantiating this subscriber builds and boots Flare.
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $this->flare->report($event->getError());
    }
}
