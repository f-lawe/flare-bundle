<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Messenger;

use Spatie\FlareClient\Contracts\JobAttributesProvider;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

/**
 * @internal
 */
final readonly class MessengerJobAttributesProvider implements JobAttributesProvider
{
    /**
     * @param list<string> $transportNames
     */
    private function __construct(
        private Envelope $envelope,
        private array $transportNames,
    ) {}

    /**
     * @param array<array-key, mixed> $senders Senders, indexed by transport name
     */
    public static function fromDispatchedEnvelope(Envelope $envelope, array $senders): self
    {
        $transportNames = $envelope->last(TransportNamesStamp::class)?->getTransportNames() ?? array_keys($senders);

        return new self($envelope, array_values(array_filter($transportNames, \is_string(...))));
    }

    public static function fromReceivedEnvelope(Envelope $envelope, string $transportName): self
    {
        return new self($envelope, [$transportName]);
    }

    #[\Override]
    public function toArray(): array
    {
        return array_filter([
            'symfony.messenger.message.class' => $this->jobClass(),
            'symfony.messenger.message.id'    => self::messageId($this->envelope),
            'symfony.messenger.bus'           => $this->envelope->last(BusNameStamp::class)?->getBusName(),
            'symfony.messenger.transports'    => $this->transportNames !== [] ? $this->transportNames : null,
            'symfony.messenger.retry_count'   => RedeliveryStamp::getRetryCountFromEnvelope($this->envelope),
        ], static fn (mixed $value): bool => $value !== null);
    }

    #[\Override]
    public function jobName(): string
    {
        return $this->jobClass();
    }

    #[\Override]
    public function jobClass(): string
    {
        return $this->envelope->getMessage()::class;
    }

    #[\Override]
    public function entryPointHandlerIdentifier(): string
    {
        return $this->jobClass();
    }

    #[\Override]
    public function entryPointHandlerName(): string
    {
        return $this->jobClass();
    }

    #[\Override]
    public function entryPointHandlerType(): string
    {
        return 'symfony_messenger_message';
    }

    private static function messageId(Envelope $envelope): ?string
    {
        $id = $envelope->last(TransportMessageIdStamp::class)?->getId();

        return \is_string($id) || \is_int($id) ? (string) $id : null;
    }
}
