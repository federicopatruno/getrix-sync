<?php

declare(strict_types=1);

namespace GetrixSync\Support;

use RuntimeException;

final class Config
{
    /**
     * @var array<string, mixed>|null
     */
    private static ?array $config = null;

    /**
     * Get a configuration value using dot notation.
     *
     * Example:
     *
     * Config::get('feed.timeout');
     */
    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        $config = self::load();

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Return the complete configuration.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::load();
    }

    /**
     * @return array<string, mixed>
     */
    private static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $file = dirname(__DIR__, 2) . '/config/plugin.php';

        if (!is_file($file)) {
            throw new RuntimeException(
                'Getrix Sync configuration file not found.'
            );
        }

        $config = require $file;

        if (!is_array($config)) {
            throw new RuntimeException(
                'Getrix Sync configuration must return an array.'
            );
        }

        self::$config = $config;

        return self::$config;
    }
}
