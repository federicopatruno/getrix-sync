<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Domain\GetrixSchemaInspector;

final class DomainServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(
            GetrixSchemaInspector::class,
            static fn(): GetrixSchemaInspector =>
            new GetrixSchemaInspector(
                dirname(__DIR__, 2)
                    . '/resources/getrix/feed_3_1_0.xsd'
            )
        );

        $container->singleton(
            GetrixCodeResolver::class,
            static fn(Container $container): GetrixCodeResolver =>
            new GetrixCodeResolver(
                $container->get(GetrixSchemaInspector::class)
            )
        );

        $container->singleton(
            GetrixPropertyMapper::class,
            static fn(Container $container): GetrixPropertyMapper =>
            new GetrixPropertyMapper(
                $container->get(GetrixCodeResolver::class)
            )
        );
    }

    public function boot(Container $container): void
    {
        // No WordPress hooks.
    }
}
