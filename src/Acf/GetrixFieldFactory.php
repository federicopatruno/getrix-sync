<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

final class GetrixFieldFactory
{
    private const FIELD_GROUP_KEY = 'group_getrix_immobile';

    /**
     * Build the complete ACF field group.
     *
     * @param array<string, array<string, mixed>> $fields
     *
     * @return array<string, mixed>
     */
    public function buildFieldGroup(
        array $fields
    ): array {
        return [
            'key' => self::FIELD_GROUP_KEY,
            'title' => 'Getrix - Immobile',
            'fields' => array_values(
                array_map(
                    fn(array $field): array =>
                    $this->buildField($field),
                    $fields
                )
            ),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'immobile',
                    ],
                ],
            ],
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => '',
        ];
    }

    /**
     * Convert a mapped Getrix field into an ACF field.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    public function buildField(
        array $field
    ): array {
        $type = (string) (
            $field['type'] ?? 'text'
        );

        $acfField = [
            'key' => $this->fieldKey(
                (string) ($field['name'] ?? '')
            ),

            'label' => (string) (
                $field['label'] ?? ''
            ),

            'name' => (string) (
                $field['name'] ?? ''
            ),

            'type' => $type,

            'required' => (
                !empty($field['required'])
            ),

            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],

            'conditional_logic' => 0,

            'instructions' => '',

            'default_value' => '',

            'placeholder' => '',

        ];

        /*
         * Select fields.
         */
        if ($type === 'select') {
            $acfField = array_merge(
                $acfField,
                [
                    'choices' => (
                        is_array($field['choices'] ?? null)
                        ? $field['choices']
                        : []
                    ),

                    'default_value' => false,

                    'return_format' => 'value',

                    'multiple' => 0,

                    'allow_null' => 1,

                    'ui' => 1,

                    'ajax' => 0,

                    'placeholder' => '',
                ]
            );
        }

        /*
         * Number fields.
         */
        if ($type === 'number') {
            $acfField = array_merge(
                $acfField,
                [
                    'default_value' => '',

                    'min' => '',

                    'max' => '',

                    'step' => '',
                ]
            );
        }

        /*
         * Date/time fields.
         *
         * We keep the ACF display format configurable here,
         * while the importer can later normalize the Getrix
         * datetime value before saving it.
         */
        if ($type === 'date_time_picker') {
            $acfField = array_merge(
                $acfField,
                [
                    'display_format' => 'd/m/Y H:i',

                    'return_format' => 'Y-m-d H:i:s',

                    'first_day' => 1,
                ]
            );
        }

        /*
         * Date fields.
         */
        if ($type === 'date_picker') {
            $acfField = array_merge(
                $acfField,
                [
                    'display_format' => 'd/m/Y',

                    'return_format' => 'Y-m-d',

                    'first_day' => 1,
                ]
            );
        }

        /*
         * Keep the source information as internal metadata.
         *
         * These values are useful to the synchronizer but
         * are not ACF settings, so they are removed before
         * the field is registered.
         */
        unset(
            $acfField['xsd_name'],
            $acfField['xsd_type'],
            $acfField['base_type'],
            $acfField['min_occurs'],
            $acfField['max_occurs']
        );

        return $acfField;
    }

    private function fieldKey(
        string $name
    ): string {
        return 'field_getrix_' . $this->sanitizeKey(
            $name
        );
    }

    private function sanitizeKey(
        string $value
    ): string {
        $value = strtolower($value);

        $value = preg_replace(
            '/[^a-z0-9_]+/',
            '_',
            $value
        ) ?? $value;

        return trim(
            $value,
            '_'
        );
    }
}
