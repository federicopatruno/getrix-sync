<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

use GetrixSync\Domain\GetrixSchemaInspector;
use RuntimeException;

final readonly class GetrixAcfBuilder
{
    public function __construct(
        private GetrixSchemaInspector $inspector,
        private GetrixFieldMapper $mapper,
    ) {}

    /**
     * Build the complete ACF field group definition
     * for Getrix Immobile.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $immobile = $this->inspector->immobile();

        if ($immobile === null) {
            throw new RuntimeException(
                'Getrix Immobile element not found in XSD.'
            );
        }

        return [
            'title' => 'Getrix - Immobile',
            'fields' => $this->buildChildren(
                $immobile['children'] ?? []
            ),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $children
     * @return array<int, array<string, mixed>>
     */
    private function buildChildren(array $children): array
    {
        $fields = [];

        foreach ($children as $child) {
            $fields[] = $this->buildField($child);
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function buildField(array $definition): array
    {
        $xsdName = (string) (
            $definition['name'] ?? ''
        );

        if ($xsdName === '') {
            throw new RuntimeException(
                'Cannot build ACF field without XSD name.'
            );
        }

        /*
         * Complex field with children.
         */
        if (
            isset($definition['children']) &&
            is_array($definition['children']) &&
            $definition['children'] !== []
        ) {
            return $this->buildComplexField(
                $definition
            );
        }

        /*
         * Simple field.
         */
        return $this->mapper->map(
            $definition
        );
    }

    /**
     * Build an ACF group/repeater field.
     *
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function buildComplexField(
        array $definition
    ): array {
        $xsdName = (string) $definition['name'];

        $name = $this->toSnakeCase($xsdName);

        $type = $this->resolveComplexFieldType(
            $definition
        );

        $field = [
            'key' => $this->makeKey($name),
            'label' => $this->makeLabel($xsdName),
            'name' => $name,
            'type' => $type,
            'required' => (
                (int) ($definition['min_occurs'] ?? 0)
            ) >= 1,
        ];

        $field['sub_fields'] = $this->buildChildren(
            $definition['children'] ?? []
        );

        return $field;
    }

    /**
     * Decide whether an XSD complex field becomes
     * an ACF group or repeater.
     *
     * @param array<string, mixed> $definition
     */
    private function resolveComplexFieldType(
        array $definition
    ): string {
        $maxOccurs = $definition['max_occurs'] ?? 1;

        /*
     * The complex element itself may occur only once,
     * while one of its children can be repeatable.
     *
     * Example:
     *
     * Descrizioni
     *   └── Descrizione maxOccurs="unbounded"
     *
     * In this case Descrizioni must become an ACF repeater.
     */
        if ($maxOccurs === 'unbounded') {
            return 'repeater';
        }

        if (
            is_int($maxOccurs) &&
            $maxOccurs > 1
        ) {
            return 'repeater';
        }

        $children = $definition['children'] ?? [];

        if (is_array($children)) {
            foreach ($children as $child) {
                $childMaxOccurs = $child['max_occurs'] ?? 1;

                if ($childMaxOccurs === 'unbounded') {
                    return 'repeater';
                }

                if (
                    is_int($childMaxOccurs) &&
                    $childMaxOccurs > 1
                ) {
                    return 'repeater';
                }
            }
        }

        return 'group';
    }

    private function toSnakeCase(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $value = preg_replace(
            '/([A-Z]+)([A-Z][a-z])/',
            '$1_$2',
            $value
        ) ?? $value;

        $value = preg_replace(
            '/([a-z0-9])([A-Z])/',
            '$1_$2',
            $value
        ) ?? $value;

        $value = strtolower($value);

        $value = preg_replace(
            '/^id_you_tube([0-9]+)$/',
            'id_youtube_$1',
            $value
        ) ?? $value;

        return $value;
    }

    private function makeLabel(string $value): string
    {
        $value = $this->toSnakeCase($value);

        return ucwords(
            str_replace(
                '_',
                ' ',
                $value
            )
        );
    }

    private function makeKey(string $name): string
    {
        return 'field_getrix_' . $name;
    }
}
