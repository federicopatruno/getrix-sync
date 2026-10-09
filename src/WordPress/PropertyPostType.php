<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;

final class PropertyPostType implements ServiceProvider
{
    public function register(Container $container): void
    {
        // No container bindings required yet.
    }

    public function boot(Container $container): void
    {
        add_action('init', [$this, 'registerPostType']);
    }

    public function registerPostType(): void
    {
        $postType = (string) Config::get('post_type', 'immobile');

        register_post_type($postType, [
            'labels' => [
                'name' => 'Immobili',
                'singular_name' => 'Immobile',
                'menu_name' => 'Immobili',
                'name_admin_bar' => 'Immobile',
                'add_new' => 'Aggiungi nuovo',
                'add_new_item' => 'Aggiungi nuovo immobile',
                'edit_item' => 'Modifica immobile',
                'new_item' => 'Nuovo immobile',
                'view_item' => 'Visualizza immobile',
                'search_items' => 'Cerca immobili',
                'not_found' => 'Nessun immobile trovato',
                'not_found_in_trash' => 'Nessun immobile nel cestino',
            ],

            'public' => true,

            'show_ui' => true,

            'show_in_menu' => true,

            'show_in_rest' => true,

            'menu_position' => 20,

            'menu_icon' => 'dashicons-building',

            'supports' => [
                'title',
                'editor',
                'thumbnail',
                'revisions',
            ],

            'has_archive' => true,

            'rewrite' => [
                'slug' => 'immobili',
                'with_front' => false,
            ],

            'capability_type' => 'post',

            'map_meta_cap' => true,
        ]);
    }
}
