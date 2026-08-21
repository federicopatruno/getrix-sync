<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Domain\Property;
use GetrixSync\Support\Config;
use RuntimeException;
use WP_Post;

final class PropertyRepository
{
    private string $postType;

    public function __construct()
    {
        $this->postType = (string) Config::get(
            'post_type',
            'immobile'
        );
    }

    /**
     * Find an immobile by its Getrix ID.
     */
    public function findByGetrixId(string $getrixId): ?WP_Post
    {
        $getrixId = trim($getrixId);

        if ($getrixId === '') {
            return null;
        }

        global $wpdb;

        $postId = $wpdb->get_var(
            $wpdb->prepare(
                "
            SELECT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p
                ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND pm.meta_value = %s
              AND p.post_type = %s
            ORDER BY pm.post_id ASC
            LIMIT 1
            ",
                PropertyMeta::GETRIX_ID,
                $getrixId,
                $this->postType
            )
        );

        if ($postId === null) {
            return null;
        }

        $post = get_post((int) $postId);

        return $post instanceof WP_Post
            ? $post
            : null;
    }

    /**
     * Create or update an immobile.
     *
     * @return array{
     *     post: WP_Post,
     *     created: bool,
     *     updated: bool
     * }
     */
    public function save(Property $property): array
    {
        $existing = $this->findByGetrixId(
            $property->getrixId
        );

        $postData = [
            'post_type' => $this->postType,
            'post_title' => $property->title(),
            'post_content' => $property->description(),
            'post_status' => 'publish',
        ];

        if ($existing === null) {
            $postId = wp_insert_post(
                $postData,
                true
            );

            if (is_wp_error($postId)) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to create immobile "%s": %s',
                        $property->getrixId,
                        $postId->get_error_message()
                    )
                );
            }

            $created = true;
            $updated = false;
        } else {
            $postData['ID'] = $existing->ID;

            $postId = wp_update_post(
                $postData,
                true
            );

            if (is_wp_error($postId)) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to update immobile "%s": %s',
                        $property->getrixId,
                        $postId->get_error_message()
                    )
                );
            }

            $created = false;
            $updated = true;
        }

        $this->saveTechnicalMeta(
            (int) $postId,
            $property
        );

        $post = get_post((int) $postId);

        if (!$post instanceof WP_Post) {
            throw new RuntimeException(
                sprintf(
                    'Unable to retrieve immobile post %d.',
                    $postId
                )
            );
        }

        return [
            'post' => $post,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * List the Getrix IDs of every "immobile" post currently in
     * WordPress (excluding trashed posts).
     *
     * @return array<int, array{id: int, getrix_id: string}>
     */
    public function allSynced(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT pm.post_id AS id, pm.meta_value AS getrix_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p
                ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND p.post_type = %s
              AND p.post_status != 'trash'
            ",
                PropertyMeta::GETRIX_ID,
                $this->postType
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            static fn(array $row): array => [
                'id' => (int) $row['id'],
                'getrix_id' => (string) $row['getrix_id'],
            ],
            $rows
        );
    }

    /**
     * Permanently delete every "immobile" post whose Getrix ID is
     * not present in the given list (i.e. properties that have been
     * removed from the feed).
     *
     * @param array<int, string> $currentGetrixIds
     *
     * @return int Number of posts deleted.
     */
    public function deleteMissing(array $currentGetrixIds): int
    {
        $currentIds = array_flip(
            array_map('strval', $currentGetrixIds)
        );

        $deleted = 0;

        foreach ($this->allSynced() as $row) {
            if (isset($currentIds[$row['getrix_id']])) {
                continue;
            }

            $result = wp_delete_post($row['id'], true);

            if ($result !== false && $result !== null) {
                ++$deleted;
            }
        }

        return $deleted;
    }

    /**
     * Save synchronization metadata.
     */
    private function saveTechnicalMeta(
        int $postId,
        Property $property
    ): void {
        update_post_meta(
            $postId,
            PropertyMeta::GETRIX_ID,
            $property->getrixId
        );

        update_post_meta(
            $postId,
            PropertyMeta::LAST_SYNC,
            current_time('mysql', true)
        );

        if (
            isset($property->data['data_modifica'])
            && $property->data['data_modifica'] !== null
        ) {
            update_post_meta(
                $postId,
                PropertyMeta::SOURCE_MODIFIED,
                (string) $property->data['data_modifica']
            );
        }
    }
}
