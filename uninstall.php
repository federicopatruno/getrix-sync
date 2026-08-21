<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

// The deactivation hook already clears the scheduled event in the
// common case, but uninstall can also happen without a prior
// deactivation (e.g. deleted directly via SFTP), so we clear it
// again defensively here.
\GetrixSync\Sync\SyncCronProvider::deactivate();
