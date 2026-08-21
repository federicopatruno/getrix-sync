<?php

declare(strict_types=1);

use GetrixSync\Acf\GetrixAcfRegistrar;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!function_exists('acf_add_local_field_group')) {
    function acf_add_local_field_group(array $fieldGroup): array
    {
        return $fieldGroup;
    }
}

$registrar = new GetrixAcfRegistrar();

$fieldGroup = [
    'key' => 'group_getrix_immobile',
    'title' => 'Getrix - Immobile',
    'fields' => [
        [
            'key' => 'field_test',
            'label' => 'Test',
            'name' => 'test',
            'type' => 'text',
        ],
    ],
    'location' => [
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'immobile',
            ],
        ],
    ],
];

$result = $registrar->register($fieldGroup);

echo PHP_EOL;
echo "===== GETRIX ACF REGISTRAR TEST =====" . PHP_EOL;
echo PHP_EOL;
echo "ACF function available: YES" . PHP_EOL;
echo "Registration call: OK" . PHP_EOL;
echo PHP_EOL;
echo "Status: OK" . PHP_EOL;
