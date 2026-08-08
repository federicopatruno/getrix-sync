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

        $posts = get_posts([
            'post_type' => $this->postType,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'all',
            'meta_key' => PropertyMeta::GETRIX_ID,
            'meta_value' => $getrixId,
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        return $posts[0] ?? null;
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
