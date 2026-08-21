<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GetrixSync\Acf\GetrixAcfFieldFactory;
use GetrixSync\Acf\GetrixAcfFieldGroupBuilder;
use GetrixSync\Domain\GetrixFieldMap;
use GetrixSync\Domain\GetrixSchemaInspector;

$inspector = new GetrixSchemaInspector(
    __DIR__ . '/resources/getrix/feed_3_1_0.xsd'
);

$map = new GetrixFieldMap();

$factory = new GetrixAcfFieldFactory();

$builder = new GetrixAcfFieldGroupBuilder(
    $inspector,
    $map,
    $factory
);

$group = $builder->build();

$immobile = $inspector->immobile();

foreach (
    [
        'Prezzo',
        'NrLocali',
        'NrVani',
        'SpeseMensili',
        'Latitudine',
        'Longitudine',
        'PubblicaMappa',
        'TrattativaRiservata',
        'Asta',
        'Pregio',
    ] as $name
) {
    echo PHP_EOL;
    echo "===== {$name} =====" . PHP_EOL;

    print_r(
        $immobile['children'][$name] ?? null
    );
}

echo 'Field Group: ' . $group['title'] . PHP_EOL;
echo 'Fields: ' . count($group['fields']) . PHP_EOL;
echo PHP_EOL;

foreach ($group['fields'] as $field) {
    echo sprintf(
        "%-30s | %-18s | required=%s",
        $field['name'],
        $field['type'],
        $field['required'] ? 'yes' : 'no'
    );

    if ($field['type'] === 'select') {
        echo ' | choices=' .
            count($field['choices'] ?? []);
    }

    if (
        in_array(
            $field['type'],
            ['group', 'repeater'],
            true
        )
    ) {
        echo ' | sub_fields=' .
            count($field['sub_fields'] ?? []);
    }

    echo PHP_EOL;
}
