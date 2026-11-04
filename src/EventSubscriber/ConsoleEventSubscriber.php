<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\EventSubscriber;

use Spatie\FlareClient\Flare;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
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
            ConsoleEvents::COMMAND   => ['onConsoleCommand', 4096],
            ConsoleEvents::ERROR     => ['onConsoleError', 0],
            ConsoleEvents::TERMINATE => [
                ['onConsoleTerminating', 4096],
                ['onConsoleTerminated', -4096],
            ],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->flare->lifecycle->booted();

        $command = $event->getCommand();

        // Long-running workers trace each unit of work as a subtask, not the worker command itself.
        if (null === $command || $this->flare->lifecycle->usesSubtasks) {
            return;
        }

        $commandClass = $command instanceof LazyCommand ? $command->getCommand()::class : $command::class;

        $this->flare->command()?->recordStartFromSymfonyInput(
            (string) $command->getName(),
            $event->getInput(),
            $commandClass,
        );
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $this->flare->report($event->getError());
    }

    public function onConsoleTerminating(ConsoleTerminateEvent $event): void
    {
        $this->flare->command()?->recordEnd($event->getExitCode());
        $this->flare->lifecycle->terminating();
    }

    public function onConsoleTerminated(): void
    {
        $this->flare->lifecycle->terminated();
    }
}
