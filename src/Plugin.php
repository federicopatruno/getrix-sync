<?php

declare(strict_types=1);

namespace GetrixSync;

final class Plugin
{
    public static function boot(): void
    {
        add_action(
            'plugins_loaded',
            [self::class, 'init']
        );
    }

    public static function init(): void
    {
        // bootstrap
    }
}
