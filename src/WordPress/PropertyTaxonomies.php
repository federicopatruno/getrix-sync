<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;

/**
 * Registers the custom taxonomies (Tipologia, Contratto, Tipologia
 * d'uso, Tipo costruzione) attached to the property post type. They
 * are defined in config/plugin.php under 'taxonomies.terms' and
 * filled by PropertyTermsWriter during the sync.
 */
final class PropertyTaxonomies implements ServiceProvider
{
    public function register(Container $container): void
    {
        // No container bindings required.
    }

    public function boot(Container $container): void
    {
        add_action('init', [$this, 'registerTaxonomies']);
    }

    public function registerTaxonomies(): void
    {
        $postType = (string) Config::get('post_type', 'immobile');

        $definitions = Config::get('taxonomies.terms', []);

        if (!is_array($definitions)) {
            return;
        }

        foreach ($definitions as $definition) {
            $taxonomy = (string) ($definition['taxonomy'] ?? '');

            if ($taxonomy === '') {
                continue;
            }

            $singular = (string) ($definition['singular'] ?? $taxonomy);
            $plural = (string) ($definition['plural'] ?? $singular);

            register_taxonomy($taxonomy, [$postType], [
                'labels' => [
                    'name' => $plural,
                    'singular_name' => $singular,
                    'menu_name' => $plural,
                    'all_items' => 'Tutti: ' . $plural,
                    'edit_item' => 'Modifica ' . $singular,
                    'view_item' => 'Visualizza ' . $singular,
                    'update_item' => 'Aggiorna ' . $singular,
                    'add_new_item' => 'Aggiungi ' . $singular,
                    'new_item_name' => 'Nuovo nome: ' . $singular,
                    'search_items' => 'Cerca: ' . $plural,
                    'not_found' => 'Nessun elemento trovato',
                ],

                /*
                 * The values come from a fixed vocabulary (feed
                 * labels), so the checklist UI of a hierarchical
                 * taxonomy suits editors better than free-text
                 * tag input. Terms themselves stay one flat level.
                 */
                'hierarchical' => true,

                'public' => true,

                'show_ui' => true,

                'show_in_rest' => true,

                'show_admin_column' => true,

                'rewrite' => [
                    'slug' => $taxonomy,
                    'with_front' => false,
                ],
            ]);
        }
    }
}
