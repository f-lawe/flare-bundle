<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Support;

use Psr\Container\ContainerInterface;
use Spatie\FlareClient\FlareProvider;
use Spatie\FlareClient\Support\Container;
use Spatie\FlareClient\Support\Exceptions\ContainerEntryNotFoundException;

/**
 * Registry of Flare's service builders: instances are owned by the Symfony container.
 * Symfony builds Flare services through build(), and the builders resolve their dependencies back through Symfony.
 *
 * @internal
 */
final class RuntimeContainer extends Container
{
    private ?FlareProvider $provider = null;

    public function __construct(
        private ContainerInterface $services,
    ) {
        parent::__construct();
    }

    public function setProvider(FlareProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function build(string $id): mixed
    {
        $builder = $this->singletons[$id] ?? $this->definitions[$id] ?? throw ContainerEntryNotFoundException::make($id);

        return $builder();
    }

    public function bootProvider(): void
    {
        $provider       = $this->provider;
        $this->provider = null;

        $provider?->boot();
    }

    #[\Override]
    public function has(string $id): bool
    {
        return $this->services->has($id) || parent::has($id);
    }

    #[\Override]
    public function resolve(string $id, array $parameters): mixed
    {
        if ($this->services->has($id)) {
            return $this->services->get($id);
        }

        return parent::resolve($id, $parameters);
    }
}
