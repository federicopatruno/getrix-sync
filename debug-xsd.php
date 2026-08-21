<?php

declare(strict_types=1);

$xsd = __DIR__ . '/resources/getrix/feed_3_1_0.xsd';

if (!is_readable($xsd)) {
    die("XSD non trovato: {$xsd}\n");
}

$xml = simplexml_load_file($xsd);

if ($xml === false) {
    die("Impossibile leggere l'XSD\n");
}

$xml->registerXPathNamespace(
    'xs',
    'http://www.w3.org/2001/XMLSchema'
);

$getrix = $xml->xpath(
    '//xs:element[@name="Getrix"]'
);

$immobile = $xml->xpath(
    '//xs:element[@name="Immobile"]'
);

echo 'Getrix: ' . count($getrix) . PHP_EOL;
echo 'Immobile: ' . count($immobile) . PHP_EOL;

if ($immobile !== []) {
    $node = $immobile[0];

    echo PHP_EOL;
    echo 'Nome: ' . (string) $node['name'] . PHP_EOL;
    echo 'maxOccurs: ' . (string) $node['maxOccurs'] . PHP_EOL;

    $fields = $node->xpath(
        './xs:complexType/xs:all/xs:element'
    );

    echo 'Campi diretti: ' . count($fields) . PHP_EOL;

    echo PHP_EOL . "Primi campi:" . PHP_EOL;

    foreach (array_slice($fields, 0, 20) as $field) {
        echo ' - ' . (string) $field['name'] . PHP_EOL;
    }
}
