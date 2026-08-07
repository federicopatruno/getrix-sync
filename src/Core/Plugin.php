<?php

declare(strict_types=1);

namespace GetrixSync\Core;

use GetrixSync\WordPress\PropertyPostType;
use GetrixSync\WordPress\WordPressServiceProvider;
use GetrixSync\WordPress\PropertyMeta;

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
            ->addProvider(new PropertyPostType())
            ->addProvider(new PropertyMeta())
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
