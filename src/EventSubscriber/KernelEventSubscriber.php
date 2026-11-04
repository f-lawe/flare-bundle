<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\EventSubscriber;

use Spatie\FlareClient\AttributesProviders\PhpRouteAttributesProvider;
use Spatie\FlareClient\Flare;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelEventSubscriber implements EventSubscriberInterface
{
    private const HEADER_TRACEPARENT   = 'traceparent';
    private const ATTRIBUTE_ROUTE      = '_route';
    private const ATTRIBUTE_CONTROLLER = '_controller';

    public function __construct(
        private Flare $flare
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 4096],
                ['onRoutingStart', 33],
                ['onRoutingEnd', 31],
            ],
            KernelEvents::EXCEPTION => ['onKernelException', 0],
            KernelEvents::RESPONSE  => ['onKernelResponse', -4096],
            KernelEvents::TERMINATE => [
                ['onKernelTerminating', 4096],
                ['onKernelTerminated', -4096],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->flare->lifecycle->usesSubtasks) {
            $this->flare->lifecycle->startSubtask($request->headers->get(self::HEADER_TRACEPARENT));
        }
        else {
            $this->flare->lifecycle->booted();
        }

        $this->flare->request()?->recordStartFromSymfonyRequest($request);
    }

    public function onRoutingStart(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->flare->routing()?->recordRoutingStart();
        }
    }

    public function onRoutingEnd(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->flare->routing()?->recordRoutingEnd($this->routeAttributesProvider($event->getRequest()));
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->flare->report($event->getThrowable());
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $this->flare->request()?->recordEndFromSymfonyResponse(
            $event->getResponse(),
            $request,
            $this->routeAttributesProvider($request),
        );
    }

    public function onKernelTerminating(): void
    {
        $this->flare->lifecycle->terminating();
    }

    public function onKernelTerminated(): void
    {
        if ($this->flare->lifecycle->usesSubtasks) {
            $this->flare->lifecycle->endSubtask();

            return;
        }

        $this->flare->lifecycle->terminated();
    }

    private function routeAttributesProvider(Request $request): PhpRouteAttributesProvider
    {
        $route = $request->attributes->get(self::ATTRIBUTE_ROUTE);

        return new PhpRouteAttributesProvider(
            route: \is_string($route) ? $route : null,
            method: $request->getMethod(),
            handlerName: self::controllerName($request->attributes->get(self::ATTRIBUTE_CONTROLLER)),
        );
    }

    private static function controllerName(mixed $controller): ?string
    {
        if (\is_string($controller)) {
            return $controller;
        }

        if (\is_array($controller) && isset($controller[0], $controller[1]) && \is_string($controller[1])) {
            $class = \is_object($controller[0]) ? $controller[0]::class : $controller[0];

            return \is_string($class) ? $class . '::' . $controller[1] : null;
        }

        if (\is_object($controller) && !$controller instanceof \Closure) {
            return $controller::class;
        }

        return null;
    }
}
