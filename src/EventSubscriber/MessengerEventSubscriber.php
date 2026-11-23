<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\EventSubscriber;

use Flawe\FlareBundle\Messenger\FlareTraceparentStamp;
use Flawe\FlareBundle\Messenger\MessengerJobAttributesProvider;
use Spatie\FlareClient\Flare;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\MessageSentToTransportsEvent;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Exception\WrappedExceptionsInterface;

/**
 * Traces messages sent to transports, and messages handled by `messenger:consume` workers.
 *
 * @internal
 */
final class MessengerEventSubscriber implements EventSubscriberInterface
{
    private const ATTRIBUTE_WILL_RETRY = 'symfony.messenger.will_retry';

    /**
     * Message handled by the worker, while its job span or subtask is open.
     */
    private ?object $handledMessage = null;

    public function __construct(
        private Flare $flare
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        $events = [
            // Lowest priorities: listeners may still replace the envelope, or skip the message.
            SendMessageToTransportsEvent::class => ['onSendMessageToTransports', -4096],
            WorkerMessageReceivedEvent::class   => ['onWorkerMessageReceived', -4096],
            WorkerMessageHandledEvent::class    => ['onWorkerMessageHandled', -4096],
            WorkerMessageFailedEvent::class     => ['onWorkerMessageFailed', -4096],
            WorkerRunningEvent::class           => ['onWorkerRunning', 4096],
        ];

        // Symfony >= 7.2
        if (class_exists(MessageSentToTransportsEvent::class)) {
            $events[MessageSentToTransportsEvent::class] = ['onMessageSentToTransports', 4096];
        }

        return $events;
    }

    public function onSendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        $envelope = $event->getEnvelope();

        $this->flare->queue()?->recordStart(MessengerJobAttributesProvider::fromDispatchedEnvelope($envelope, $event->getSenders()));

        $traceparent = $this->flare->tracer->traceParent();

        if ($traceparent !== null) {
            $event->setEnvelope($envelope->withoutAll(FlareTraceparentStamp::class)->with(new FlareTraceparentStamp($traceparent)));
        }

        // Without MessageSentToTransportsEvent, the sending itself is not measured.
        if (!class_exists(MessageSentToTransportsEvent::class)) {
            $this->flare->queue()?->recordEnd();
        }
    }

    public function onMessageSentToTransports(): void
    {
        $this->flare->queue()?->recordEnd();
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        $jobRecorder = $this->flare->job();

        if ($jobRecorder === null || !$event->shouldHandle()) {
            return;
        }

        $this->endHandledMessage();

        $envelope = $event->getEnvelope();

        $this->handledMessage = $envelope->getMessage();

        $jobRecorder->recordStart(
            MessengerJobAttributesProvider::fromReceivedEnvelope($envelope, $event->getReceiverName()),
            $envelope->last(FlareTraceparentStamp::class)?->traceparent,
        );
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        if ($this->isHandledMessage($event->getEnvelope()->getMessage())) {
            $this->handledMessage = null;
            $this->flare->job()?->recordEnd();
        }
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $exceptions = self::unwrapExceptions($event->getThrowable());

        if ($this->isHandledMessage($event->getEnvelope()->getMessage())) {
            $this->handledMessage = null;
            $this->flare->job()?->recordFailed($exceptions[0], [
                self::ATTRIBUTE_WILL_RETRY => $event->willRetry(),
            ]);
        }

        // Reported after the job span is ended, for the report to be attached to it.
        foreach ($exceptions as $exception) {
            $this->flare->report($exception);
        }
    }

    /**
     * Ends the job of a message that was neither handled nor failed, e.g. when a listener threw.
     */
    public function onWorkerRunning(): void
    {
        $this->endHandledMessage();
    }

    private function endHandledMessage(): void
    {
        if ($this->handledMessage !== null) {
            $this->handledMessage = null;
            $this->flare->job()?->recordEnd();
        }
    }

    /**
     * Batch handlers may acknowledge a message while another one is handled.
     */
    private function isHandledMessage(object $message): bool
    {
        return $this->handledMessage !== null && $this->handledMessage === $message;
    }

    /**
     * @return non-empty-list<\Throwable>
     */
    private static function unwrapExceptions(\Throwable $throwable): array
    {
        if (!$throwable instanceof WrappedExceptionsInterface) {
            return [$throwable];
        }

        $exceptions = array_values($throwable->getWrappedExceptions(recursive: true));

        return $exceptions !== [] ? $exceptions : [$throwable];
    }
}
