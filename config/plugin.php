<?php

declare(strict_types=1);

return [
    'post_type' => 'immobile',

    'option_name' => 'getrix_sync',

    'cron_hook' => 'getrix_sync_run',

    'rest_namespace' => 'getrix-sync/v1',

    'version' => '0.1.0',

    'feed' => [
        'url' => 'https://studiostilo.it/wp-content/feeds/B84A402B-0D84-4D26-BFDD-4EE8BB05605E.xml',
        'xsd_url' => 'http://feed.getrix.it/xml/feed_3_1_0.xsd',
        'timeout' => 60,
        'connect_timeout' => 15,
    ],

    'sync' => [
        'delete_missing' => true,
        'download_images' => true,
    ],
];
