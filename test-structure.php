<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GetrixSync\Domain\GetrixSchemaInspector;
use GetrixSync\Acf\GetrixAcfStructureBuilder;

$schemaPath = __DIR__ . '/resources/getrix/feed_3_1_0.xsd';

$inspector = new GetrixSchemaInspector($schemaPath);

$immobile = $inspector->immobile();

if ($immobile === null) {
    die("IMMOBILE NOT FOUND\n");
}

$builder = new GetrixAcfStructureBuilder();

$fields = $builder->build($immobile);

echo "GETRIX ACF STRUCTURE DRY RUN\n";
echo "============================\n\n";

echo 'Top-level fields: ' . count($fields) . "\n\n";

foreach ($fields as $field) {
    echo sprintf(
        "%-30s | %-18s | required=%s",
        $field['name'],
        $field['type'],
        $field['required'] ? 'yes' : 'no'
    );

    if (
        isset($field['sub_fields']) &&
        is_array($field['sub_fields'])
    ) {
        echo ' | sub-fields=' . count($field['sub_fields']);
    }

    echo "\n";
}

echo "\n";

$totalSubFields = 0;

foreach ($fields as $field) {
    if (
        isset($field['sub_fields']) &&
        is_array($field['sub_fields'])
    ) {
        $totalSubFields += count($field['sub_fields']);
    }
}

echo 'Total sub-fields: ' . $totalSubFields . "\n";
echo "Status: OK\n";

foreach (
    [
        'MQSuperficie',
        'IDCantiereSoluzione',
        'DataInserimento',
        'DataModifica',
    ] as $fieldName
) {
    echo "\n===== {$fieldName} =====\n";

    print_r(
        $inspector->immobile()['children'][$fieldName] ?? null
    );
}


foreach (
    [
        'Descrizione',
        'Immagine',
        'Allegato',
    ] as $fieldName
) {
    echo "\n===== {$fieldName} =====\n";

    print_r(
        $inspector->element($fieldName)
    );
}
