<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GetrixSync\Domain\GetrixSchemaInspector;

$inspector = new GetrixSchemaInspector(
    __DIR__ . '/resources/getrix/feed_3_1_0.xsd'
);

$immobile = $inspector->immobile();

if ($immobile === null) {
    die("Immobile non trovato\n");
}

$fields = [
    'Categoria',
    'Contratto',
    'TipoSpese',
    'TipoProprieta',
];

foreach ($fields as $name) {
    echo PHP_EOL;
    echo $name . PHP_EOL;
    echo str_repeat('-', strlen($name)) . PHP_EOL;

    $field = $immobile['children'][$name] ?? null;

    if ($field === null) {
        echo "NON TROVATO\n";
        continue;
    }

    echo 'type: ' . ($field['type'] ?? 'inline') . PHP_EOL;

    if (empty($field['enumeration'])) {
        echo "Nessuna enumeration diretta\n";
        continue;
    }

    foreach ($field['enumeration'] as $code => $item) {
        echo sprintf(
            "  %s => %s\n",
            $code,
            $item['label']
        );
    }
}
