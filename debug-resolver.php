<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GetrixSync\Domain\GetrixCodeResolver;
use GetrixSync\Domain\GetrixSchemaInspector;

$inspector = new GetrixSchemaInspector(
    __DIR__ . '/resources/getrix/feed_3_1_0.xsd'
);

$resolver = new GetrixCodeResolver(
    $inspector
);

echo "Categoria 2: ";
echo $resolver->label('Categoria', 2);
echo PHP_EOL;

echo "Contratto V: ";
echo $resolver->label('Contratto', 'V');
echo PHP_EOL;

echo "TipoSpese 1: ";
echo $resolver->label('TipoSpese', 1);
echo PHP_EOL;

echo "TipoProprieta 4: ";
echo $resolver->label('TipoProprieta', 4);
echo PHP_EOL;

echo PHP_EOL;
echo "Categoria choices:" . PHP_EOL;

print_r(
    $resolver->choices('Categoria')
);
