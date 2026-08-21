<?php

declare(strict_types=1);

namespace GetrixSync\Core;

use RuntimeException;

final class Container
{
    /**
     * @var array<string, callable(self): mixed>
     */
    private array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Register a singleton service.
     *
     * @param callable(self): mixed $factory
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /**
     * Resolve a service.
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!array_key_exists($id, $this->bindings)) {
            throw new RuntimeException(
                sprintf('Service "%s" is not registered.', $id)
            );
        }

        $instance = ($this->bindings[$id])($this);

        $this->instances[$id] = $instance;

        return $instance;
    }

    /**
     * Check whether a service is registered.
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings);
    }
}
