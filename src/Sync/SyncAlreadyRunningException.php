<?php

declare(strict_types=1);

namespace GetrixSync\Sync;

use RuntimeException;

/**
 * Thrown by SyncManager when a sync run is skipped because another
 * one already holds the lock. This is an expected, benign condition
 * (not a failure), so callers should generally log/report it
 * differently from a real sync error.
 */
final class SyncAlreadyRunningException extends RuntimeException
{
}
