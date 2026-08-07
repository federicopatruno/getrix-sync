<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;

final class WordPressServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // WordPress services will be registered here.
    }

    public function boot(Container $container): void
    {
        // WordPress hooks will be initialized here.
    }
}
