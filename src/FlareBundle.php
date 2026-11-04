<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Flawe\FlareBundle\DependencyInjection\FlareExtension;
use Spatie\FlareClient\Flare;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FlareBundle extends AbstractBundle
{
    private const SERVER_HTTP_TRACEPARENT = 'HTTP_TRACEPARENT';

    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FlareExtension();
    }

    #[\Override]
    public function boot(): void
    {
        $flare = $this->container?->get('flare');

        if (!$flare instanceof Flare) {
            return;
        }

        $traceparent = $_SERVER[self::SERVER_HTTP_TRACEPARENT] ?? null;

        // Both are no-ops in subtask mode, where subscribers drive one subtask per unit of work.
        $flare->lifecycle->start(traceparent: \is_string($traceparent) ? $traceparent : null);
        $flare->lifecycle->boot();

        if (!self::hasSymfonyErrorHandler()) {
            $flare->registerExceptionHandler();
        }
    }

    private static function hasSymfonyErrorHandler(): bool
    {
        $handler = set_exception_handler('var_dump');
        restore_exception_handler();

        return \is_array($handler) && $handler[0] instanceof ErrorHandler;
    }
}
