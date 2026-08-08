<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

final readonly class Property
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $descriptions
     * @param array<string, mixed> $commercial
     * @param array<int, array<string, mixed>> $images
     */
    public function __construct(
        public string $getrixId,
        public array $data,
        public array $descriptions,
        public array $commercial,
        public array $images,
    ) {}

    public function title(): string
    {
        $description = $this->descriptions[0] ?? [];

        $title = trim((string) ($description['titolo'] ?? ''));

        if ($title !== '') {
            return $title;
        }

        $type = trim((string) ($this->data['tipologia'] ?? ''));
        $city = trim((string) ($this->data['comune'] ?? ''));

        return trim($type . ' - ' . $city);
    }

    public function description(): string
    {
        $description = $this->descriptions[0] ?? [];

        return trim((string) ($description['testo'] ?? ''));
    }

    public function shortDescription(): string
    {
        $description = $this->descriptions[0] ?? [];

        return trim((string) ($description['testo_breve'] ?? ''));
    }
}
