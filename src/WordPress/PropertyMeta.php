<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;

final class PropertyMeta implements ServiceProvider
{
    /*
     * No leading underscore: matches the meta key naming already
     * used by the existing production data (confirmed against the
     * real database), where these are stored as plain "getrix_id",
     * "getrix_source_hash", etc. -- not the "_"-prefixed ("protected")
     * convention WordPress itself uses for internal meta.
     *
     * This matters specifically for GETRIX_ID: it's the key
     * PropertyRepository::findByGetrixId() searches on to decide
     * insert vs. update. A mismatch here means every sync silently
     * fails to find the already-existing post and creates a
     * duplicate instead of updating it.
     */
    public const GETRIX_ID = 'getrix_id';

    public const SOURCE_HASH = 'getrix_source_hash';

    public const LAST_SYNC = 'getrix_last_sync';

    public const SOURCE_MODIFIED = 'getrix_source_modified';

    /*
     * Tracks which Getrix image (by its own id_immagine, not a WP
     * attachment ID) is currently set as the post's featured image,
     * so re-syncing doesn't re-download and re-attach the same cover
     * photo every day when nothing changed.
     */
    public const FEATURED_IMAGE_SOURCE = 'getrix_featured_image_source';

    /*
     * IDs of the terms assigned by the sync itself (not registered:
     * internal bookkeeping, never exposed). Used to replace only the
     * terms the sync previously added when a listing's values
     * change, without touching terms an editor added by hand.
     * Custom taxonomies use SYNCED_TERMS_PREFIX . '<taxonomy>'.
     * SYNCED_CATEGORY_IDS and SYNCED_TAG_IDS are legacy (earlier
     * versions used the built-in category / post_tag): only read to
     * detach those terms from the post once.
     */
    public const SYNCED_CATEGORY_IDS = 'getrix_synced_category_ids';

    public const SYNCED_TERMS_PREFIX = 'getrix_synced_terms_';

    public const SYNCED_TAG_IDS = 'getrix_synced_tag_ids';

    public function register(Container $container): void
    {
        // No container bindings required yet.
    }

    public function boot(Container $container): void
    {
        add_action('init', [$this, 'registerMeta']);
    }

    public function registerMeta(): void
    {
        $postType = (string) Config::get('post_type', 'immobile');

        register_post_meta(
            $postType,
            self::GETRIX_ID,
            [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]
        );

        register_post_meta(
            $postType,
            self::SOURCE_HASH,
            [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]
        );

        register_post_meta(
            $postType,
            self::LAST_SYNC,
            [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]
        );

        register_post_meta(
            $postType,
            self::SOURCE_MODIFIED,
            [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]
        );

        register_post_meta(
            $postType,
            self::FEATURED_IMAGE_SOURCE,
            [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]
        );
    }
}
