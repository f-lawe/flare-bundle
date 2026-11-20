<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\Support;

use Spatie\FlareClient\Support\Container;

/**
 * @internal
 */
final class DryRunContainer extends Container
{
    /** @var array<class-string, bool> */
    private array $services = [];

    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    public function singleton(string $class, array|\Closure|string|null $builder = null): void
    {
        $this->services[$class] = true;
    }

    #[\Override]
    public function bind(string $class, ?\Closure $builder = null): void
    {
        $this->services[$class] = false;
    }

    /**
     * @return array<class-string, bool> Service ids, mapped to whether they are shared
     */
    public function services(): array
    {
        return $this->services;
    }
}
