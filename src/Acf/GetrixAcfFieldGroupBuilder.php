<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

use GetrixSync\Domain\GetrixFieldMap;
use GetrixSync\Domain\GetrixSchemaInspector;

final class GetrixAcfFieldGroupBuilder
{
    public function __construct(
        private readonly GetrixSchemaInspector $inspector,
        private readonly GetrixFieldMap $fieldMap,
        private readonly GetrixAcfFieldFactory $factory,
    ) {}

    /**
     * Build the ACF field group definition for Immobile.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $immobile = $this->inspector->immobile();

        if ($immobile === null) {
            throw new \RuntimeException(
                'Unable to find Immobile definition in Getrix XSD.'
            );
        }

        $fields = [];

        foreach ($immobile['children'] ?? [] as $getrixName => $definition) {
            $acfName = $this->fieldMap->acfName(
                $getrixName
            );

            $fields[] = $this->factory->make(
                $getrixName,
                $definition,
                $acfName
            );
        }

        return [
            'key' => 'group_getrix_immobile',
            'title' => 'Getrix - Immobile',
            'fields' => $fields,
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'immobile',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => [],
            'active' => true,
            'description' => 'Campi sincronizzati dal feed Getrix.',
        ];
    }
}
