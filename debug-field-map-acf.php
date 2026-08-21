<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GetrixSync\Domain\GetrixFieldMap;

$map = new GetrixFieldMap();

$fields = [
    'Categoria',
    'Contratto',
    'NrLocali',
    'MQSuperficie',
    'DataInserimento',
    'DataModifica',
    'NrCamereLetto',
    'DataConsegnaCostruzione',
    'URLVirtualTour',
    'SituazioneImmobile',
];

foreach ($fields as $field) {
    echo sprintf(
        "%-30s => %s\n",
        $field,
        $map->acfName($field)
    );
}
