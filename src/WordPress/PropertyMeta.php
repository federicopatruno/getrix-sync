<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;

final class PropertyMeta implements ServiceProvider
{
    public const GETRIX_ID = '_getrix_id';

    public const SOURCE_HASH = '_getrix_source_hash';

    public const LAST_SYNC = '_getrix_last_sync';

    public const SOURCE_MODIFIED = '_getrix_source_modified';

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
    }
}
