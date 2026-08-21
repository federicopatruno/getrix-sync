<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

final class GetrixAcfStructureBuilder
{
    /**
     * @param array<string, mixed> $immobile
     * @return array<int, array<string, mixed>>
     */
    public function build(
        array $immobile
    ): array {
        $fields = [];

        foreach ($immobile['children'] ?? [] as $name => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $fields[] = $this->buildField(
                (string) $name,
                $definition
            );
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function buildField(
        string $xsdName,
        array $definition
    ): array {
        $name = $this->fieldName($xsdName);

        /*
         * Elemento con figli:
         *
         * - Descrizioni -> repeater
         * - Immagini     -> repeater
         * - Allegati     -> repeater
         * - Residenziale -> group
         * - Commerciale  -> group
         * - Attivita     -> group
         * - Terreno      -> group
         * - Vacanze      -> group
         */
        if (!empty($definition['children'])) {
            $type = $this->containerType(
                $xsdName
            );

            $field = [
                'key' => 'field_getrix_' . $name,
                'label' => $this->label($xsdName),
                'name' => $name,
                'type' => $type,
                'required' => (
                    (int) ($definition['min_occurs'] ?? 0)
                ) > 0 ? 1 : 0,
                'menu_order' => 0,
                'instructions' => '',
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
            ];

            $subFields = [];

            foreach (
                $definition['children']
                as $childName => $childDefinition
            ) {
                if (!is_array($childDefinition)) {
                    continue;
                }

                $subFields[] = $this->buildField(
                    (string) $childName,
                    $childDefinition
                );
            }

            $field['sub_fields'] = $subFields;

            if ($type === 'repeater') {
                $field['min'] = (
                    (int) ($definition['min_occurs'] ?? 0)
                ) > 0 ? 1 : 0;

                $field['max'] = 0;

                $field['layout'] = 'table';

                $field['button_label'] = 'Aggiungi';
            }

            return $field;
        }

        return $this->buildScalarField(
            $xsdName,
            $definition
        );
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function buildScalarField(
        string $xsdName,
        array $definition
    ): array {
        $name = $this->fieldName($xsdName);

        $field = [
            'key' => 'field_getrix_' . $name,
            'label' => $this->label($xsdName),
            'name' => $name,
            'type' => $this->acfType($definition),
            'required' => (
                (int) ($definition['min_occurs'] ?? 0)
            ) > 0 ? 1 : 0,
            'menu_order' => 0,
            'instructions' => '',
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
        ];

        $choices = $definition['enumeration'] ?? [];

        if (is_array($choices) && $choices !== []) {
            $field['type'] = 'select';
            $field['choices'] = [];

            foreach ($choices as $value => $choice) {
                if (!is_array($choice)) {
                    continue;
                }

                $field['choices'][(string) $value] =
                    (string) (
                        $choice['label'] ??
                        $value
                    );
            }

            $field['allow_null'] = 1;
            $field['multiple'] = 0;
            $field['return_format'] = 'value';
            $field['ui'] = 1;

            return $field;
        }

        switch ($field['type']) {
            case 'number':
                $field['min'] = '';
                $field['max'] = '';
                $field['step'] = '';
                $field['prepend'] = '';
                $field['append'] = '';
                break;

            case 'date_time_picker':
                $field['display_format'] = 'd/m/Y H:i';
                $field['return_format'] = 'Y-m-d H:i:s';
                $field['first_day'] = 1;
                break;

            case 'text':
            default:
                $field['default_value'] = '';
                $field['maxlength'] = '';
                $field['placeholder'] = '';
                break;
        }

        return $field;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function acfType(
        array $field
    ): string {
        $type = $field['type'] ?? null;

        if ($type === null || $type === '') {
            $type = $field['base_type'] ?? null;
        }

        if ($type === null || $type === '') {
            return 'text';
        }

        // Normalizza eventuali namespace XSD.
        $type = str_replace(
            '\\',
            '',
            strtolower((string) $type)
        );

        $type = preg_replace(
            '/^xs:/',
            '',
            $type
        ) ?? $type;

        return match ($type) {
            'date',
            'datetime' => 'date_time_picker',

            'integer',
            'nonnegativeinteger',
            'positiveinteger',
            'unsignedbyte',
            'unsignedshort',
            'unsignedint',
            'unsignedlong',
            'decimal',
            'float',
            'double' => 'number',

            'boolean' => 'true_false',

            default => 'text',
        };
    }

    private function containerType(
        string $xsdName
    ): string {
        return match ($xsdName) {
            'Descrizioni',
            'Immagini',
            'Allegati' => 'repeater',

            default => 'group',
        };
    }

    private function fieldName(
        string $name
    ): string {
        $specialCases = [
            'YouTube' => 'youtube',
            'MQ' => 'mq',
            'URL' => 'url',
            'ID' => 'id',
            'Nr' => 'nr',
        ];

        foreach ($specialCases as $prefix => $replacement) {
            if (str_starts_with($name, $prefix)) {
                $name = $replacement . substr(
                    $name,
                    strlen($prefix)
                );

                break;
            }
        }

        $name = preg_replace(
            '/([a-z\d])([A-Z])/',
            '$1_$2',
            $name
        ) ?? $name;

        $name = preg_replace(
            '/([A-Za-z])(\d+)/',
            '$1_$2',
            $name
        ) ?? $name;

        return strtolower($name);
    }

    private function label(
        string $name
    ): string {
        $name = preg_replace(
            '/(?<!^)([A-Z])/',
            ' $1',
            $name
        ) ?? $name;

        return ucfirst(
            trim($name)
        );
    }
}
