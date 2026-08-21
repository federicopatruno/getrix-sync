<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

final class GetrixCodeResolver
{
    public function __construct(
        private readonly GetrixSchemaInspector $inspector
    ) {}

    /**
     * Resolve a Getrix code to its XSD label.
     */
    public function label(
        string $field,
        string|int|null $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $immobile = $this->inspector->immobile();

        if ($immobile === null) {
            return null;
        }

        $definition =
            $immobile['children'][$field] ?? null;

        if ($definition === null) {
            return null;
        }

        $code = (string) $value;

        return $definition['enumeration'][$code]['label']
            ?? null;
    }

    /**
     * Return all allowed values for an enum field.
     *
     * @return array<string, string>
     */
    public function choices(
        string $field
    ): array {
        $immobile = $this->inspector->immobile();

        if ($immobile === null) {
            return [];
        }

        $definition =
            $immobile['children'][$field] ?? null;

        if ($definition === null) {
            return [];
        }

        $choices = [];

        foreach (
            $definition['enumeration'] ?? []
            as $code => $item
        ) {
            $choices[(string) $code] =
                (string) $item['label'];
        }

        return $choices;
    }

    public function has(
        string $field,
        string|int|null $value
    ): bool {
        return $this->label(
            $field,
            $value
        ) !== null;
    }
}
