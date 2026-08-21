<?php

declare(strict_types=1);

use GetrixSync\Acf\GetrixFieldMapper;

require_once __DIR__ . '/../vendor/autoload.php';

$mapper = new GetrixFieldMapper();

$fields = [
    [
        'name' => 'Prezzo',
        'type' => 'xs:decimal',
        'base_type' => null,
        'min_occurs' => 1,
        'max_occurs' => 1,
        'enumeration' => [],
    ],
    [
        'name' => 'MQSuperficie',
        'type' => 'xs:nonNegativeInteger',
        'base_type' => null,
        'min_occurs' => 1,
        'max_occurs' => 1,
        'enumeration' => [],
    ],
    [
        'name' => 'DataInserimento',
        'type' => 'xs:dateTime',
        'base_type' => null,
        'min_occurs' => 1,
        'max_occurs' => 1,
        'enumeration' => [],
    ],
    [
        'name' => 'Categoria',
        'type' => null,
        'base_type' => 'xs:unsignedByte',
        'min_occurs' => 1,
        'max_occurs' => 1,
        'enumeration' => [
            '1' => [
                'value' => '1',
                'label' => 'Immobili Residenziali',
            ],
            '2' => [
                'value' => '2',
                'label' => 'Immobili Commerciali',
            ],
        ],
    ],
];

foreach ($fields as $field) {
    print_r($mapper->map($field));
    echo PHP_EOL;
}
