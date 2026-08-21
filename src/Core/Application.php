<?php

declare(strict_types=1);

namespace GetrixSync\Core;

final class Application
{
    private Container $container;

    /**
     * @var list<ServiceProvider>
     */
    private array $providers = [];

    public function __construct()
    {
        $this->container = new Container();
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function addProvider(ServiceProvider $provider): self
    {
        $this->providers[] = $provider;

        return $this;
    }

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $provider->register($this->container);
        }
    }

    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot($this->container);
        }
    }

    public function run(): void
    {
        $this->register();

        $this->boot();
    }
}
