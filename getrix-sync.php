<?php

/**
 * Plugin Name: Getrix Sync
 * Plugin URI: https://github.com/federicopatruno/getrix-sync
 * Description: Synchronize Getrix XML feeds into WordPress.
 * Version: 0.1.0
 * Requires PHP: 8.2
 * Requires at least: 6.8
 * Author: Federico Patruno
 * License: GPL-2.0-or-later
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

require __DIR__ . '/vendor/autoload.php';

register_activation_hook(
    __FILE__,
    [\GetrixSync\Sync\SyncCronProvider::class, 'activate']
);

register_deactivation_hook(
    __FILE__,
    [\GetrixSync\Sync\SyncCronProvider::class, 'deactivate']
);

\GetrixSync\Core\Plugin::boot();
