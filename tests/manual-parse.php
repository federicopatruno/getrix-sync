<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use GetrixSync\Feed\FeedDownloader;
use GetrixSync\Feed\GetrixParser;
use GetrixSync\Feed\GetrixValidator;

$downloader = new FeedDownloader();

$xml = $downloader->downloadFrom(
    'https://studiostilo.it/wp-content/feeds/B84A402B-0D84-4D26-BFDD-4EE8BB05605E.xml'
);

$validator = new GetrixValidator();

$validator->validate($xml);

echo 'XSD validation: OK' . PHP_EOL;

$parser = new GetrixParser();

$feed = $parser->parse($xml);

echo 'Version: ' . $feed->version . PHP_EOL;
echo 'User: ' . $feed->user . PHP_EOL;
echo 'Immobili: ' . $feed->count() . PHP_EOL;

if ($feed->properties !== []) {
    $property = $feed->properties[0];

    echo PHP_EOL;
    echo 'Primo immobile:' . PHP_EOL;
    echo 'ID: ' . ($property['getrix_id'] ?? '') . PHP_EOL;
    echo 'Comune: ' . ($property['comune'] ?? '') . PHP_EOL;
    echo 'Tipologia: ' . ($property['tipologia'] ?? '') . PHP_EOL;
    echo 'Prezzo: ' . ($property['prezzo'] ?? '') . PHP_EOL;
    echo 'Immagini: ' . count($property['immagini'] ?? []) . PHP_EOL;
}
