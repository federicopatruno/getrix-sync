<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

final class GetrixAcfFieldFactory
{
    /**
     * Build an ACF field definition from a Getrix/XSD definition.
     *
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    public function make(
        string $getrixName,
        array $definition,
        string $acfName
    ): array {
        $type = $this->acfType($definition);

        $field = [
            'key' => $this->key($acfName),
            'label' => $this->label($getrixName),
            'name' => $acfName,
            'type' => $type,
            'instructions' => '',
            'required' => $this->isRequired($definition),
            'conditional_logic' => 0,
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
        ];

        if ($type === 'select') {
            $field['choices'] = $this->choices(
                $definition['enumeration'] ?? []
            );

            $field['return_format'] = 'value';
            $field['allow_null'] = 1;
            $field['multiple'] = 0;
            $field['ui'] = 0;
            $field['ajax'] = 0;
            $field['placeholder'] = '';
        }

        if ($type === 'number') {
            $field['default_value'] = '';
            $field['min'] = '';
            $field['max'] = '';
            $field['step'] = '';
            $field['prepend'] = '';
            $field['append'] = '';
        }

        if ($type === 'true_false') {
            $field['message'] = '';
            $field['default_value'] = 0;
            $field['ui'] = 1;
            $field['ui_on_text'] = '';
            $field['ui_off_text'] = '';
        }

        if ($type === 'date_picker') {
            $field['display_format'] = 'd/m/Y';
            $field['return_format'] = 'Y-m-d';
            $field['first_day'] = 1;
        }

        if ($type === 'date_time_picker') {
            $field['display_format'] = 'd/m/Y H:i:s';
            $field['return_format'] = 'Y-m-d H:i:s';
            $field['first_day'] = 1;
        }

        if ($type === 'url') {
            $field['placeholder'] = '';
        }

        if ($type === 'group') {
            $field['layout'] = 'block';
            $field['sub_fields'] = $this->children(
                $definition['children'] ?? []
            );
        }

        if ($type === 'repeater') {
            $field['layout'] = 'table';
            $field['min'] = 0;
            $field['max'] = 0;
            $field['collapsed'] = '';
            $field['button_label'] = 'Aggiungi';

            $field['sub_fields'] = $this->repeaterChildren(
                $definition
            );
        }

        return $field;
    }

    /**
     * @param array<string, array<string, mixed>> $definitions
     * @return array<int, array<string, mixed>>
     */
    private function children(array $definitions): array
    {
        $fields = [];

        foreach ($definitions as $getrixName => $definition) {
            $acfName = $this->acfName($getrixName);

            $fields[] = $this->make(
                $getrixName,
                $definition,
                $acfName
            );
        }

        return $fields;
    }

    /**
     * Build the fields contained inside a Getrix collection.
     *
     * Example:
     *
     * Descrizioni
     *   └── Descrizione
     *       ├── Titolo
     *       ├── Testo
     *       └── TestoBreve
     *
     * becomes:
     *
     * ACF Repeater
     *   ├── titolo
     *   ├── testo
     *   └── testo_breve
     *
     * @param array<string, mixed> $definition
     * @return array<int, array<string, mixed>>
     */
    private function repeaterChildren(array $definition): array
    {
        $children = $definition['children'] ?? [];

        if (!is_array($children) || count($children) !== 1) {
            return $this->children($children);
        }

        $child = reset($children);

        if (!is_array($child)) {
            return [];
        }

        /*
     * If the repeated element itself contains children,
     * those children become the repeater sub-fields.
     */
        if (!empty($child['children'])) {
            return $this->children(
                $child['children']
            );
        }

        /*
     * Otherwise the repeated element is itself the value.
     *
     * Example:
     *
     * Immagini
     *   └── Immagine maxOccurs="unbounded"
     *
     * becomes a repeater containing one field:
     *
     * immagine
     */
        $childName = array_key_first($children);

        if ($childName === null) {
            return [];
        }

        $acfName = $this->acfName(
            $childName
        );

        return [
            $this->make(
                $childName,
                $child,
                $acfName
            ),
        ];
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function acfType(array $definition): string
    {
        /*
     * A Getrix collection is represented in the XSD as:
     *
     * Descrizioni
     *   └── Descrizione maxOccurs="unbounded"
     *
     * Immagini
     *   └── Immagine maxOccurs="unbounded"
     *
     * The parent itself may have maxOccurs="1".
     */
        if ($this->isCollection($definition)) {
            return 'repeater';
        }

        if (!empty($definition['children'])) {
            return 'group';
        }

        if (!empty($definition['enumeration'])) {
            return 'select';
        }

        $type = (string) ($definition['type'] ?? '');

        return match ($type) {
            'xs:boolean',
            'boolean',
            'bool' => 'true_false',

            'xs:dateTime',
            'dateTime' => 'date_time_picker',

            'xs:date',
            'date' => 'date_picker',

            'xs:integer',
            'xs:int',
            'xs:nonNegativeInteger',
            'xs:positiveInteger',
            'integer',
            'int' => 'number',

            'xs:decimal',
            'xs:double',
            'xs:float',
            'decimal',
            'double',
            'float' => 'number',

            default => $this->stringType($type),
        };
    }

    /**
     * Determine whether an XSD structure should become an ACF Repeater.
     *
     * @param array<string, mixed> $definition
     */
    private function isRepeater(array $definition): bool
    {
        if (empty($definition['children'])) {
            return false;
        }

        $maxOccurs = $definition['max_occurs'] ?? 1;

        if ($maxOccurs === 'unbounded') {
            return true;
        }

        if (is_numeric($maxOccurs) && (int) $maxOccurs > 1) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the definition represents a repeating
     * collection such as Descrizioni, Immagini or Allegati.
     *
     * @param array<string, mixed> $definition
     */
    private function isCollection(array $definition): bool
    {
        $children = $definition['children'] ?? [];

        if (!is_array($children) || count($children) !== 1) {
            return false;
        }

        $child = reset($children);

        if (!is_array($child)) {
            return false;
        }

        $maxOccurs = $child['max_occurs'] ?? 1;

        return $maxOccurs === 'unbounded'
            || (is_numeric($maxOccurs) && (int) $maxOccurs > 1);
    }

    private function stringType(string $type): string
    {
        if (str_contains(strtolower($type), 'url')) {
            return 'url';
        }

        return 'text';
    }

    /**
     * @param array<string, array<string, mixed>> $enumeration
     * @return array<string, string>
     */
    private function choices(array $enumeration): array
    {
        $choices = [];

        foreach ($enumeration as $code => $item) {
            $choices[(string) $code] =
                (string) ($item['label'] ?? $code);
        }

        return $choices;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function isRequired(array $definition): bool
    {
        return (int) (
            $definition['min_occurs'] ?? 0
        ) > 0;
    }

    private function key(string $acfName): string
    {
        return 'field_getrix_' . $acfName;
    }

    private function label(string $getrixName): string
    {
        return $getrixName;
    }

    private function acfName(string $getrixName): string
    {
        $value = preg_replace(
            '/(?<!^)[A-Z]/',
            '_$0',
            $getrixName
        ) ?? $getrixName;

        return strtolower($value);
    }
}
