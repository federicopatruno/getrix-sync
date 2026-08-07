<?php

/**
 * Plugin Name: Getrix Sync
 * Plugin URI: https://github.com/federicopatruno/getrix-sync
 * Description: Synchronize Getrix XML feeds into WordPress.
 * Version: 0.1.0
 * Requires PHP: 8.2
 * Requires at least: 6.8
 * Author: Federico Patruno
 * License: GPL2
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

require __DIR__ . '/vendor/autoload.php';

GetrixSync\Plugin::boot();
