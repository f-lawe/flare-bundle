<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ConfigurationEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    )
    {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // if (!key_exists('flare.key', $_ENV)) {
        //     $this->logger->warning('The "flare.key" is not properly set. Please configure it.');
        // }

        // Log a warning if the setting is not properly set
        $this->logger->warning('The "flare_bundle.some_setting" is not properly set. Please configure it.');
    }
}
