<?php

declare(strict_types=1);

namespace GetrixSync\Sync;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Domain\GetrixPropertyMapper;
use GetrixSync\Feed\FeedDownloader;
use GetrixSync\Feed\GetrixParser;
use GetrixSync\Feed\GetrixValidator;
use GetrixSync\WordPress\PropertyAcfWriter;
use GetrixSync\WordPress\PropertyRepository;

final class SyncServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(
            SyncManager::class,
            static fn(Container $container): SyncManager =>
            new SyncManager(
                downloader: $container->get(
                    FeedDownloader::class
                ),
                validator: $container->get(
                    GetrixValidator::class
                ),
                parser: $container->get(
                    GetrixParser::class
                ),
                mapper: $container->get(
                    GetrixPropertyMapper::class
                ),
                repository: $container->get(
                    PropertyRepository::class
                ),
                acfWriter: $container->get(
                    PropertyAcfWriter::class
                ),
                lock: new SyncLock(),
            )
        );
    }

    public function boot(Container $container): void
    {
        // Sync hooks will be registered later.
    }
}
