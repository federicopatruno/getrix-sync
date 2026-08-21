<?php

declare(strict_types=1);

use GetrixSync\Acf\GetrixAcfBuilder;
use GetrixSync\Acf\GetrixFieldMapper;
use GetrixSync\Domain\GetrixSchemaInspector;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$schemaPath = dirname(__DIR__) . '/resources/getrix/feed_3_1_0.xsd';

$inspector = new GetrixSchemaInspector($schemaPath);
$mapper = new GetrixFieldMapper();

$builder = new GetrixAcfBuilder(
    $inspector,
    $mapper
);

$acf = $builder->build();

echo PHP_EOL;
echo "===== GETRIX ACF BUILDER TEST =====" . PHP_EOL;
echo PHP_EOL;

echo 'Field Group: ' . $acf['title'] . PHP_EOL;
echo 'Top-level fields: ' . count($acf['fields']) . PHP_EOL;
echo PHP_EOL;

$totalSubFields = 0;

foreach ($acf['fields'] as $field) {
    $name = $field['name'] ?? '';
    $type = $field['type'] ?? '';
    $required = !empty($field['required'])
        ? 'yes'
        : 'no';

    $line = sprintf(
        '%-32s | %-15s | required=%s',
        $name,
        $type,
        $required
    );

    if (
        isset($field['sub_fields']) &&
        is_array($field['sub_fields'])
    ) {
        $count = count($field['sub_fields']);

        $line .= sprintf(
            ' | sub-fields=%d',
            $count
        );

        $totalSubFields += $count;
    }

    echo $line . PHP_EOL;
}

echo PHP_EOL;
echo 'Total direct sub-fields: ' . $totalSubFields . PHP_EOL;
echo PHP_EOL;
echo 'Status: OK' . PHP_EOL;
