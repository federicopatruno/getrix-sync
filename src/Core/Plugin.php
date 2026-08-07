<?php

declare(strict_types=1);

namespace GetrixSync\Core;

use GetrixSync\WordPress\WordPressServiceProvider;

final class Plugin
{
    private static ?Application $application = null;

    public static function boot(): void
    {
        if (self::$application !== null) {
            return;
        }

        self::$application = new Application();

        self::$application
            ->addProvider(new WordPressServiceProvider())
            ->run();
    }

    public static function application(): Application
    {
        if (self::$application === null) {
            self::boot();
        }

        return self::$application;
    }

    public static function container(): Container
    {
        return self::application()->container();
    }
}
