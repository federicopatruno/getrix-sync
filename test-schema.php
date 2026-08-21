<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GetrixSync\Domain\GetrixSchemaInspector;

$inspector = new GetrixSchemaInspector(
    __DIR__ . '/resources/getrix/feed_3_1_0.xsd'
);

$immobile = $inspector->immobile();

var_dump($immobile);
