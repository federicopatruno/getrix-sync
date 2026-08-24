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
 * IMPORTANT: this deliberately does NOT use add_option() /
 * get_option() / delete_option(). Since WordPress 6.4, add_option()
 * issues "INSERT ... ON DUPLICATE KEY UPDATE" internally, guarded by
 * a separate, non-atomic get_option()/cache pre-check -- so two
 * near-simultaneous add_option() calls for the same key can BOTH
 * return true. It is not a usable mutex primitive.
 *
 * Instead this talks to $wpdb->options directly with a plain INSERT
 * (via $wpdb->insert(), no upsert clause). option_name has a UNIQUE
 * key in WordPress's schema, so when two processes race, the
 * database itself guarantees exactly one INSERT succeeds and the
 * other fails with a duplicate-key error -- a real, database-level
 * atomic guarantee, independent of any PHP-level cache.
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
        $this->reclaimIfStale();

        global $wpdb;

        /*
         * A duplicate-key failure here is an expected, routine
         * outcome (another run holds the lock), not a real error, so
         * suppress $wpdb's default error output for the duration of
         * this single query.
         */
        $previous = $wpdb->suppress_errors(true);

        $inserted = $wpdb->insert(
            $wpdb->options,
            [
                'option_name' => $this->key,
                'option_value' => (string) time(),
                'autoload' => 'no',
            ],
            ['%s', '%s', '%s']
        );

        $wpdb->suppress_errors($previous);

        if ($inserted === false) {
            return false;
        }

        $this->bustCache();

        $this->held = true;

        return true;
    }

    public function release(): void
    {
        if (!$this->held) {
            return;
        }

        $this->deleteRow();

        $this->held = false;
    }

    /**
     * Reclaim a lock left behind by a run that crashed or timed out
     * (fatal error, PHP max_execution_time, server restart) without
     * releasing it -- otherwise a single failed run would block
     * every future sync forever.
     */
    private function reclaimIfStale(): void
    {
        global $wpdb;

        $acquiredAt = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options}"
                    . ' WHERE option_name = %s LIMIT 1',
                $this->key
            )
        );

        if ($acquiredAt === null) {
            return;
        }

        if ((time() - (int) $acquiredAt) < $this->ttl) {
            return;
        }

        $this->deleteRow();
    }

    private function deleteRow(): void
    {
        global $wpdb;

        $wpdb->delete(
            $wpdb->options,
            ['option_name' => $this->key],
            ['%s']
        );

        $this->bustCache();
    }

    /**
     * Writing to wp_options directly (bypassing add_option() /
     * update_option() / delete_option()) means WordPress's own
     * option caches are not automatically kept in sync. Nothing else
     * in this plugin reads this option through get_option(), but we
     * bust the relevant cache groups anyway so a well-behaved
     * get_option($this->key) call from anywhere else (another
     * plugin, a debug script) never sees stale data.
     */
    private function bustCache(): void
    {
        wp_cache_delete($this->key, 'options');
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');
    }
}
