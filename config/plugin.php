<?php

declare(strict_types=1);

return [
    'post_type' => 'immobile',

    'option_name' => 'getrix_sync',

    'cron_hook' => 'getrix_sync_run',

    'rest_namespace' => 'getrix-sync/v1',

    'version' => '0.1.0',

    'feed' => [
        'timeout' => 60,
        'connect_timeout' => 15,
    ],

    'sync' => [
        'delete_missing' => true,
        'download_images' => true,
    ],
];
