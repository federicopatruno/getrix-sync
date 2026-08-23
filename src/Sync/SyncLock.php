<?php

declare(strict_types=1);

namespace GetrixSync\Sync;

use GetrixSync\Support\Config;

/**
 * A simple mutex used to make sure only one sync run (cron-triggered
 * "sync all", the manual "sync all" button, or a single-property
 * sync) executes at a time.
 *
 * Without this, two overlapping runs can both call
 * PropertyRepository::findByGetrixId() for the same property before
 * either has inserted it, and both then insert -> duplicate post.
 * "Check if it exists, otherwise insert" is not atomic on its own,
 * so the mutual exclusion has to happen one level up, around the
 * whole sync run.
 *
 * Implemented with wp_options instead of transients because
 * add_option() relies on the UNIQUE index on option_name: when two
 * requests call it for the same key at the same time, the database
 * guarantees exactly one INSERT succeeds. A transient-based lock
 * (get_transient/set_transient) does not offer the same guarantee on
 * every object cache backend.
 */
final class SyncLock
{
    private string $key;
    private int $ttl;
    private bool $held = false;

    public function __construct()
    {
        $this->key = (string) Config::get(
            'sync.lock_key',
            'getrix_sync_lock'
        );

        $this->ttl = (int) Config::get(
            'sync.lock_ttl',
            600
        );
    }

    /**
     * Try to acquire the lock. Returns false if another sync run
     * already holds it (and it is not stale).
     */
    public function acquire(): bool
    {
        if (add_option($this->key, time(), '', 'no')) {
            $this->held = true;

            return true;
        }

        /*
         * The lock already exists. If it is older than the TTL, the
         * process that created it almost certainly crashed or timed
         * out (fatal error, PHP max_execution_time, server restart)
         * without releasing it, so we reclaim it defensively rather
         * than blocking every future sync forever.
         */
        $acquiredAt = (int) get_option($this->key, 0);

        if ($acquiredAt > 0 && (time() - $acquiredAt) < $this->ttl) {
            return false;
        }

        delete_option($this->key);

        if (add_option($this->key, time(), '', 'no')) {
            $this->held = true;

            return true;
        }

        return false;
    }

    public function release(): void
    {
        if (!$this->held) {
            return;
        }

        delete_option($this->key);

        $this->held = false;
    }
}
