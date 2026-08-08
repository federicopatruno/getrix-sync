<?php

declare(strict_types=1);

namespace GetrixSync\Feed;

final readonly class GetrixFeed
{
    /**
     * @param array<int, array<string, mixed>> $properties
     */
    public function __construct(
        public string $version,
        public string $user,
        public array $properties,
    ) {}

    public function count(): int
    {
        return count($this->properties);
    }
}
