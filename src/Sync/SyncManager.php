<?php

declare(strict_types=1);

namespace GetrixSync\Sync;

use GetrixSync\Domain\GetrixPropertyMapper;
use GetrixSync\Domain\Property;
use GetrixSync\Feed\FeedDownloader;
use GetrixSync\Feed\GetrixParser;
use GetrixSync\Feed\GetrixValidator;
use GetrixSync\Support\Config;
use GetrixSync\WordPress\PropertyAcfWriter;
use GetrixSync\WordPress\PropertyRepository;
use RuntimeException;

final class SyncManager
{
    public function __construct(
        private readonly FeedDownloader $downloader,
        private readonly GetrixValidator $validator,
        private readonly GetrixParser $parser,
        private readonly GetrixPropertyMapper $mapper,
        private readonly PropertyRepository $repository,
        private readonly PropertyAcfWriter $acfWriter,
    ) {}

    /**
     * Download and parse the complete feed.
     */
    public function loadFeed(): \GetrixSync\Feed\GetrixFeed
    {
        $xml = $this->downloader->download();

        $this->validator->validate($xml);

        return $this->parser->parse($xml);
    }

    /**
     * Synchronize one property identified by Getrix ID.
     *
     * @return array{
     *     getrix_id: string,
     *     post_id: int,
     *     created: bool,
     *     updated: bool
     * }
     */
    public function syncOne(string $getrixId): array
    {
        $feed = $this->loadFeed();

        foreach ($feed->properties as $propertyData) {
            if (
                (string) ($propertyData['getrix_id'] ?? '')
                !== $getrixId
            ) {
                continue;
            }

            $property = $this->mapper->map($propertyData);

            return $this->persist($property);
        }

        throw new RuntimeException(
            sprintf(
                'Getrix property "%s" was not found in the feed.',
                $getrixId
            )
        );
    }

    /**
     * Synchronize all properties from the feed.
     *
     * @return array{
     *     total: int,
     *     created: int,
     *     updated: int
     * }
     */
    public function syncAll(): array
    {
        $feed = $this->loadFeed();

        $created = 0;
        $updated = 0;

        foreach ($feed->properties as $propertyData) {
            $property = $this->mapper->map($propertyData);

            $result = $this->persist($property);

            if ($result['created']) {
                ++$created;
            }

            if ($result['updated']) {
                ++$updated;
            }
        }

        return [
            'total' => count($feed->properties),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Synchronize all properties from the feed and remove any
     * "immobile" post whose Getrix ID is no longer present in the
     * feed.
     *
     * This is the method invoked by the daily WP-Cron job
     * (see SyncCronProvider).
     *
     * @return array{
     *     total: int,
     *     created: int,
     *     updated: int,
     *     deleted: int
     * }
     */
    public function syncAndPrune(): array
    {
        $feed = $this->loadFeed();

        $created = 0;
        $updated = 0;
        $currentGetrixIds = [];

        foreach ($feed->properties as $propertyData) {
            $property = $this->mapper->map($propertyData);

            $currentGetrixIds[] = $property->getrixId;

            $result = $this->persist($property);

            if ($result['created']) {
                ++$created;
            }

            if ($result['updated']) {
                ++$updated;
            }
        }

        $deleted = 0;

        if ((bool) Config::get('sync.delete_missing', true)) {
            $deleted = $this->repository->deleteMissing(
                $currentGetrixIds
            );
        }

        return [
            'total' => count($feed->properties),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
        ];
    }

    /**
     * @return array{
     *     getrix_id: string,
     *     post_id: int,
     *     created: bool,
     *     updated: bool
     * }
     */
    private function persist(Property $property): array
    {
        $result = $this->repository->save($property);

        $this->acfWriter->write(
            $result['post']->ID,
            $property
        );

        return [
            'getrix_id' => $property->getrixId,
            'post_id' => $result['post']->ID,
            'created' => $result['created'],
            'updated' => $result['updated'],
        ];
    }
}
