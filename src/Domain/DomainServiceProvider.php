<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;

final class DomainServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(
            GetrixPropertyMapper::class,
            static fn(): GetrixPropertyMapper =>
            new GetrixPropertyMapper()
        );
    }

    public function boot(Container $container): void
    {
        // No WordPress hooks.
    }
}
