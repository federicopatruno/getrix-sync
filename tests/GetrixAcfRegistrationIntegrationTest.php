<?php

declare(strict_types=1);

use GetrixSync\Acf\GetrixAcfBuilder;
use GetrixSync\Acf\GetrixAcfRegistrar;
use GetrixSync\Acf\GetrixFieldMapper;
use GetrixSync\Domain\GetrixSchemaInspector;

require_once dirname(__DIR__) . '/src/Autoloader.php';

echo "===== GETRIX ACF REGISTRATION INTEGRATION TEST =====\n\n";

/*
 * ---------------------------------------------------------
 * Fake ACF
 * ---------------------------------------------------------
 */

$registeredFieldGroup = null;

if (!function_exists('acf_add_local_field_group')) {
    function acf_add_local_field_group(
        array $fieldGroup
    ): void {
        global $registeredFieldGroup;

        $registeredFieldGroup = $fieldGroup;
    }
}

/*
 * ---------------------------------------------------------
 * Schema
 * ---------------------------------------------------------
 */

$schemaPath = dirname(__DIR__) . '/resources/getrix/feed_3_1_0.xsd';

$inspector = new GetrixSchemaInspector(
    $schemaPath
);

/*
 * ---------------------------------------------------------
 * Builder
 * ---------------------------------------------------------
 */

$mapper = new GetrixFieldMapper();

$builder = new GetrixAcfBuilder(
    $inspector,
    $mapper
);

$fieldGroup = $builder->build();

/*
 * ---------------------------------------------------------
 * Basic validation
 * ---------------------------------------------------------
 */

if (
    !isset($fieldGroup['title']) ||
    $fieldGroup['title'] !== 'Getrix - Immobile'
) {
    throw new RuntimeException(
        'Invalid ACF field group title.'
    );
}

if (
    !isset($fieldGroup['fields']) ||
    !is_array($fieldGroup['fields'])
) {
    throw new RuntimeException(
        'ACF field group fields are missing.'
    );
}

$fields = $fieldGroup['fields'];

if (count($fields) !== 57) {
    throw new RuntimeException(
        sprintf(
            'Expected 57 top-level fields, got %d.',
            count($fields)
        )
    );
}

/*
 * ---------------------------------------------------------
 * Registrar
 * ---------------------------------------------------------
 */

$registrar = new GetrixAcfRegistrar();

$registrar->register(
    $fieldGroup
);

/*
 * ---------------------------------------------------------
 * Verify registration
 * ---------------------------------------------------------
 */

if ($registeredFieldGroup === null) {
    throw new RuntimeException(
        'ACF field group was not registered.'
    );
}

if (
    $registeredFieldGroup['title']
    !== 'Getrix - Immobile'
) {
    throw new RuntimeException(
        'Registered field group has invalid title.'
    );
}

if (
    count($registeredFieldGroup['fields'])
    !== 57
) {
    throw new RuntimeException(
        'Registered field group does not contain 57 top-level fields.'
    );
}

/*
 * ---------------------------------------------------------
 * Nested fields
 * ---------------------------------------------------------
 */

$expectedComplexFields = [
    'descrizioni' => 'repeater',
    'residenziale' => 'group',
    'commerciale' => 'group',
    'attivita' => 'group',
    'terreno' => 'group',
    'vacanze' => 'group',
    'immagini' => 'repeater',
    'allegati' => 'repeater',
];

foreach ($expectedComplexFields as $name => $expectedType) {
    $found = null;

    foreach ($registeredFieldGroup['fields'] as $field) {
        if (($field['name'] ?? null) === $name) {
            $found = $field;
            break;
        }
    }

    if ($found === null) {
        throw new RuntimeException(
            sprintf(
                'Expected field "%s" was not registered.',
                $name
            )
        );
    }

    if (($found['type'] ?? null) !== $expectedType) {
        throw new RuntimeException(
            sprintf(
                'Field "%s" expected type "%s", got "%s".',
                $name,
                $expectedType,
                $found['type'] ?? 'NULL'
            )
        );
    }

    if (
        !isset($found['sub_fields']) ||
        !is_array($found['sub_fields'])
    ) {
        throw new RuntimeException(
            sprintf(
                'Field "%s" has no sub_fields.',
                $name
            )
        );
    }
}

/*
 * ---------------------------------------------------------
 * Result
 * ---------------------------------------------------------
 */

echo "ACF function available: YES\n";
echo "Field group built: YES\n";
echo "Top-level fields: 57\n";
echo "Registration call: OK\n";
echo "Nested structures: OK\n";
echo "\nStatus: OK\n";
