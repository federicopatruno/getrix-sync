<?php

declare(strict_types=1);

use GetrixSync\Acf\GetrixAcfStructureBuilder;
use GetrixSync\Domain\GetrixSchemaInspector;

require_once dirname(__DIR__) . '/src/Autoloader.php';

$schemaPath = dirname(__DIR__) . '/resources/getrix/feed_3_1_0.xsd';

$inspector = new GetrixSchemaInspector(
    $schemaPath
);

$immobile = $inspector->immobile();

if ($immobile === null) {
    throw new RuntimeException(
        'Immobile structure not found in XSD.'
    );
}

$builder = new GetrixAcfStructureBuilder();

$result = $builder->build(
    $immobile
);

echo "===== GETRIX ACF NESTED STRUCTURE TEST =====\n\n";

foreach ($result as $field) {
    $name = $field['name'] ?? 'UNKNOWN';
    $type = $field['type'] ?? 'UNKNOWN';

    echo sprintf(
        "%-35s | %-15s",
        $name,
        $type
    );

    if (isset($field['sub_fields'])) {
        echo sprintf(
            " | sub-fields=%d",
            count($field['sub_fields'])
        );
    }

    echo "\n";
}

echo "\nStatus: OK\n";
