<?php

declare(strict_types=1);

namespace GetrixSync\Sync;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;
use Throwable;

/**
 * Schedules and runs the daily Getrix feed synchronization.
 *
 * The Getrix feed referenced in config/plugin.php is regenerated
 * every day at 03:00 Europe/Rome time. This provider schedules a
 * WP-Cron event anchored at 04:00 UTC (one hour later, to give the
 * upstream feed generator a safety margin) that downloads the feed,
 * creates/updates every "immobile" post and removes the ones that
 * are no longer present in the feed.
 */
final class SyncCronProvider implements ServiceProvider
{
    /**
     * WP-Cron only guarantees "daily" as an out-of-the-box recurring
     * interval; the exact time of day is controlled by the timestamp
     * passed to wp_schedule_event(), which is why we recompute the
     * next occurrence of 04:00 UTC ourselves instead of relying on a
     * custom cron schedule.
     */
    private const RUN_HOUR_UTC = 4;

    public function register(Container $container): void
    {
        // No container bindings required.
    }

    public function boot(Container $container): void
    {
        add_action('init', [$this, 'ensureScheduled']);

        add_action(
            $this->hook(),
            [$this, 'run']
        );
    }

    /**
     * Make sure the recurring event exists.
     *
     * This runs on every `init` (cheap: wp_next_scheduled() is a
     * single, cached option lookup) instead of relying solely on the
     * plugin activation hook, so the schedule self-heals if it was
     * ever cleared or if the plugin files were updated without a
     * deactivate/reactivate cycle.
     */
    public function ensureScheduled(): void
    {
        if (wp_next_scheduled($this->hook()) !== false) {
            return;
        }

        wp_schedule_event(
            self::nextRunTimestamp(),
            'daily',
            $this->hook()
        );
    }

    /**
     * Schedule the event immediately. Called on plugin activation.
     */
    public static function activate(): void
    {
        $hook = (string) Config::get(
            'cron_hook',
            'getrix_sync_run'
        );

        if (wp_next_scheduled($hook) !== false) {
            return;
        }

        wp_schedule_event(
            self::nextRunTimestamp(),
            'daily',
            $hook
        );
    }

    /**
     * Remove the scheduled event. Called on plugin deactivation.
     */
    public static function deactivate(): void
    {
        $hook = (string) Config::get(
            'cron_hook',
            'getrix_sync_run'
        );

        $timestamp = wp_next_scheduled($hook);

        while ($timestamp !== false) {
            wp_unschedule_event($timestamp, $hook);
            $timestamp = wp_next_scheduled($hook);
        }
    }

    /**
     * Cron callback: run a full sync and prune posts missing from
     * the feed.
     */
    public function run(): void
    {
        try {
            $manager = \GetrixSync\Core\Plugin::container()
                ->get(SyncManager::class);

            $result = $manager->syncAndPrune();

            $this->log(sprintf(
                'Sync completed: %d total, %d created, %d updated, %d deleted.',
                $result['total'],
                $result['created'],
                $result['updated'],
                $result['deleted']
            ));
        } catch (SyncAlreadyRunningException $exception) {
            /*
             * Benign: another run (cron or manual) is already in
             * progress. Not logged as an error to avoid noise; the
             * other run will complete the sync.
             */
            $this->log($exception->getMessage());
        } catch (Throwable $exception) {
            $this->log(
                'Sync failed: ' . $exception->getMessage(),
                true
            );
        }
    }

    private static function nextRunTimestamp(): int
    {
        $today = gmdate('Y-m-d');

        $timestamp = strtotime(
            sprintf(
                '%s %02d:00:00 UTC',
                $today,
                self::RUN_HOUR_UTC
            )
        );

        if ($timestamp === false) {
            // Extremely defensive fallback; should never happen.
            return time() + DAY_IN_SECONDS;
        }

        if ($timestamp <= time()) {
            $timestamp += DAY_IN_SECONDS;
        }

        return $timestamp;
    }

    private function hook(): string
    {
        return (string) Config::get(
            'cron_hook',
            'getrix_sync_run'
        );
    }

    private function log(string $message, bool $isError = false): void
    {
        if (!function_exists('error_log')) {
            return;
        }

        error_log(
            sprintf(
                '[GetrixSync]%s %s',
                $isError ? ' ERROR:' : '',
                $message
            )
        );
    }
}
