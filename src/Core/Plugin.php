<?php

declare(strict_types=1);

namespace GetrixSync\Core;

use GetrixSync\WordPress\PropertyPostType;
use GetrixSync\WordPress\PropertyTaxonomies;
use GetrixSync\WordPress\WordPressServiceProvider;
use GetrixSync\WordPress\PropertyMeta;
use GetrixSync\Acf\PropertyFields;
use GetrixSync\Feed\FeedServiceProvider;
use GetrixSync\Domain\DomainServiceProvider;
use GetrixSync\Sync\SyncServiceProvider;
use GetrixSync\Sync\SyncCronProvider;
use GetrixSync\WordPress\SyncAdminPage;

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
            ->addProvider(new PropertyTaxonomies())
            ->addProvider(new PropertyMeta())
            ->addProvider(new PropertyFields())
            ->addProvider(new FeedServiceProvider())
            ->addProvider(new DomainServiceProvider())
            ->addProvider(new SyncServiceProvider())
            ->addProvider(new SyncCronProvider())
            ->addProvider(new SyncAdminPage())
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
