<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

final class GetrixFieldMapper
{
    /**
     * Map an XSD element definition to an ACF field definition.
     *
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    public function map(array $element): array
    {
        $xsdName = (string) ($element['name'] ?? '');

        $baseType = $element['base_type']
            ?? $element['type']
            ?? null;

        $field = [
            'name' => $this->toSnakeCase($xsdName),
            'label' => $this->toLabel($xsdName),
            'type' => $this->mapAcfType($element),
            'required' => ((int) ($element['min_occurs'] ?? 0)) > 0,
            'xsd_name' => $xsdName,
            'xsd_type' => $element['type'] ?? null,
            'base_type' => $baseType,
            'min_occurs' => $element['min_occurs'] ?? 0,
            'max_occurs' => $element['max_occurs'] ?? 1,
        ];

        $choices = $element['enumeration'] ?? [];

        if (is_array($choices) && $choices !== []) {
            $field['choices'] = $this->mapChoices($choices);
        }

        return $field;
    }

    /**
     * Map an XSD element to an ACF field type.
     *
     * @param array<string, mixed> $element
     */
    private function mapAcfType(array $element): string
    {
        $enumeration = $element['enumeration'] ?? [];

        if (
            is_array($enumeration) &&
            $enumeration !== []
        ) {
            return 'select';
        }

        $type = (string) (
            $element['base_type']
            ?? $element['type']
            ?? ''
        );

        return match ($type) {
            'xs:decimal',
            'xs:integer',
            'xs:int',
            'xs:long',
            'xs:short',
            'xs:byte',
            'xs:nonNegativeInteger',
            'xs:positiveInteger',
            'xs:unsignedByte',
            'xs:unsignedShort',
            'xs:unsignedInt',
            'xs:unsignedLong',
            => 'number',

            'xs:dateTime' => 'date_time_picker',

            'xs:boolean' => 'true_false',

            default => 'text',
        };
    }

    /**
     * @param array<string, mixed> $choices
     * @return array<string, string>
     */
    private function mapChoices(array $choices): array
    {
        $result = [];

        foreach ($choices as $value => $choice) {
            if (is_array($choice)) {
                $label = (string) (
                    $choice['label']
                    ?? $choice['value']
                    ?? $value
                );

                $result[(string) $value] = $label;
                continue;
            }

            $result[(string) $value] = (string) $choice;
        }

        return $result;
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

        // Getrix-specific acronym normalization.
        $value = preg_replace(
            '/^id_you_tube([0-9]+)$/',
            'id_youtube_$1',
            $value
        ) ?? $value;

        return $value;
    }

    private function toLabel(string $value): string
    {
        $value = $this->toSnakeCase($value);

        $value = str_replace(
            '_',
            ' ',
            $value
        );

        return ucwords($value);
    }
}
