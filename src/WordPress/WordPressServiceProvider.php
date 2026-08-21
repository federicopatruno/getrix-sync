<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;

final class WordPressServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(
            PropertyRepository::class,
            static fn(): PropertyRepository =>
            new PropertyRepository()
        );

        $container->singleton(
            PropertyAcfWriter::class,
            static fn(): PropertyAcfWriter =>
            new PropertyAcfWriter()
        );
    }

    public function boot(Container $container): void
    {
        // WordPress hooks will be initialized here.
    }
}
