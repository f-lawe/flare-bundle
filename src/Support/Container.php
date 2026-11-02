<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Support;

use Psr\Container\ContainerInterface;
use Spatie\FlareClient\Support\Container as FlareClientContainer;

/**
 * Flare's container where services tagged "flare.service" in Symfony take precedence over Flare's own bindings.
 */
class Container extends FlareClientContainer
{
    public function __construct(
        private ContainerInterface $overrides,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function singleton(string $class, array|\Closure|string|null $builder = null): void
    {
        if ($this->overrides->has($class)) {
            return;
        }

        parent::singleton($class, $builder);
    }

    #[\Override]
    public function bind(string $class, ?\Closure $builder = null): void
    {
        if ($this->overrides->has($class)) {
            return;
        }

        parent::bind($class, $builder);
    }

    #[\Override]
    public function has(string $id): bool
    {
        return $this->overrides->has($id) || parent::has($id);
    }

    #[\Override]
    public function resolve(string $id, array $parameters): mixed
    {
        if ($this->overrides->has($id)) {
            return $this->overrides->get($id);
        }

        return parent::resolve($id, $parameters);
    }
}
