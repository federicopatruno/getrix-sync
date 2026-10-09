<?php

declare(strict_types=1);

return [
    'post_type' => 'immobile',

    'option_name' => 'getrix_sync',

    'cron_hook' => 'getrix_sync_run',

    'rest_namespace' => 'getrix-sync/v1',

    /*
     * Custom taxonomies filled by the sync (see
     * PropertyTermsWriter), registered by PropertyTaxonomies and
     * attached to the property post type only (so they never mix
     * with the built-in category/tag of regular posts). Each one is
     * filled from the feed field named in 'field' (looked up in the
     * common, Commerciale, Residenziale and Terreno data of the
     * listing).
     */
    'taxonomies' => [
        'terms' => [
            'categoria' => [
                'taxonomy' => 'categoria-immobile',
                'singular' => 'Categoria Immobile',
                'plural' => 'Categorie Immobile',
                'field' => 'categoria_label',
            ],
            'tipologia' => [
                'taxonomy' => 'tipologia',
                'singular' => 'Tipologia',
                'plural' => 'Tipologie',
                'field' => 'tipologia',
            ],
            'contratto' => [
                'taxonomy' => 'contratto',
                'singular' => 'Contratto',
                'plural' => 'Contratti',
                'field' => 'contratto_label',
            ],
            'tipologia_uso' => [
                'taxonomy' => 'tipologia-uso',
                'singular' => "Tipologia d'uso",
                'plural' => "Tipologie d'uso",
                'field' => 'tipologia_uso_label',
            ],
            'tipo_costruzione' => [
                'taxonomy' => 'tipo-costruzione',
                'singular' => 'Tipo costruzione',
                'plural' => 'Tipi costruzione',
                'field' => 'tipo_costruzione_label',
            ],
        ],
    ],

    'version' => '0.1.0',

    'feed' => [
        'url' => 'https://studiostilo.it/wp-content/feeds/B84A402B-0D84-4D26-BFDD-4EE8BB05605E.xml',
        'xsd_url' => 'http://feed.getrix.it/xml/feed_3_1_0.xsd',

        /*
         * Validating against the bundled copy avoids making the
         * daily sync depend on feed.getrix.it being reachable. Set
         * to null to always fetch 'xsd_url' remotely instead.
         */
        'xsd_local_path' => dirname(__DIR__)
            . '/resources/getrix/feed_3_1_0.xsd',

        'timeout' => 60,
        'connect_timeout' => 15,
    ],

    'sync' => [
        'delete_missing' => true,
        'download_images' => true,

        /*
         * Prevents two sync runs (WP-Cron, the manual "sync all"
         * button, or a single-property sync) from ever executing
         * concurrently, which would otherwise let two overlapping
         * runs both see "no existing post" for the same getrix_id
         * and both insert it, creating a duplicate.
         */
        'lock_key' => 'getrix_sync_lock',
        'lock_ttl' => 600, // seconds; safety net for a crashed run
    ],
];
