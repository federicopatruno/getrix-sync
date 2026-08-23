<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

final class GetrixCodeResolver
{
    /**
     * Per-block enum map cache: ['Immobile' => ['categoria' => ['1'
     * => 'Immobili Residenziali', ...], ...], 'Commerciale' => [...]].
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private array $enumMapCache = [];

    public function __construct(
        private readonly GetrixSchemaInspector $inspector
    ) {}

    /**
     * Resolve a Getrix code to its XSD label, for a top-level
     * Immobile field.
     *
     * Kept for backward compatibility: $field is the raw XSD element
     * name (e.g. 'Categoria'), not the snake_case field name used
     * elsewhere in the project. Prefer resolve() for new code.
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
     * Return all allowed values for a top-level Immobile enum field.
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

    /**
     * Resolve a Getrix code to its XSD label for a given block and a
     * snake_case field name (the naming convention used by
     * GetrixParser / GetrixPropertyMapper / PropertyAcfWriter).
     *
     * $block is one of 'Immobile' (top-level), 'Commerciale',
     * 'Residenziale' or 'Terreno'.
     */
    public function resolve(
        string $block,
        string $field,
        string|int|null $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        $map = $this->enumMap($block);

        return $map[$field][(string) $value] ?? null;
    }

    /**
     * Build [snake_case_field => [code => label]] for every enum
     * field defined within a block.
     *
     * @return array<string, array<string, string>>
     */
    public function enumMap(string $block): array
    {
        if (isset($this->enumMapCache[$block])) {
            return $this->enumMapCache[$block];
        }

        $children = $this->blockChildren($block);

        $map = [];

        foreach ($children ?? [] as $name => $definition) {
            $enumeration = $definition['enumeration'] ?? [];

            if ($enumeration === []) {
                continue;
            }

            $labels = [];

            foreach ($enumeration as $code => $item) {
                $labels[(string) $code] = (string) (
                    $item['label'] ?? $code
                );
            }

            $map[$this->toSnakeCase($name)] = $labels;
        }

        return $this->enumMapCache[$block] = $map;
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function blockChildren(string $block): ?array
    {
        $immobile = $this->inspector->immobile();

        if ($immobile === null) {
            return null;
        }

        if ($block === 'Immobile') {
            return $immobile['children'] ?? null;
        }

        return $immobile['children'][$block]['children']
            ?? null;
    }

    /**
     * Same PascalCase -> snake_case convention used by
     * GetrixParser::fieldName(), without the identifier special
     * cases (not relevant for enum field names).
     */
    private function toSnakeCase(string $name): string
    {
        $name = preg_replace(
            '/([A-Z]+)([A-Z][a-z])/',
            '$1_$2',
            $name
        ) ?? $name;

        $name = preg_replace(
            '/([a-z0-9])([A-Z])/',
            '$1_$2',
            $name
        ) ?? $name;

        return strtolower(trim($name, '_'));
    }
}
