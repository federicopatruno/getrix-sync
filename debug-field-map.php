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

echo "GETRIX → IMMOBILE FIELD MAP\n";
echo "===========================\n\n";

foreach ($immobile['children'] as $name => $field) {
    echo $name;

    echo ' | type=' . (
        $field['type'] ?? 'inline'
    );

    echo ' | min=' . (
        $field['min_occurs'] ?? 1
    );

    echo ' | max=' . (
        $field['max_occurs'] ?? 1
    );

    if (!empty($field['attributes'])) {
        echo ' | attributes=';

        $attributes = [];

        foreach ($field['attributes'] as $attribute => $definition) {
            $attributes[] = $attribute;
        }

        echo implode(',', $attributes);
    }

    if (!empty($field['enumeration'])) {
        echo ' | ENUM';

        foreach ($field['enumeration'] as $code => $item) {
            echo ' [' . $code . '=' . $item['label'] . ']';
        }
    }

    if (!empty($field['children'])) {
        echo ' | CHILDREN=' .
            implode(',', array_keys($field['children']));
    }

    echo PHP_EOL;
}
