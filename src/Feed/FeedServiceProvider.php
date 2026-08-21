<?php

declare(strict_types=1);

namespace GetrixSync\Feed;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;

final class FeedServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(
            FeedDownloader::class,
            static fn(): FeedDownloader => new FeedDownloader()
        );

        $container->singleton(
            GetrixParser::class,
            static fn(): GetrixParser => new GetrixParser()
        );

        $container->singleton(
            GetrixValidator::class,
            static fn(): GetrixValidator => new GetrixValidator()
        );
    }

    public function boot(Container $container): void
    {
        // Feed services do not register WordPress hooks yet.
    }
}
