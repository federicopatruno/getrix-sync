<?php

declare(strict_types=1);

use GetrixSync\Domain\GetrixPropertyMapper;
use GetrixSync\Feed\FeedDownloader;
use GetrixSync\Feed\GetrixParser;
use GetrixSync\Feed\GetrixValidator;
use GetrixSync\WordPress\PropertyAcfWriter;
use GetrixSync\WordPress\PropertyRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

$downloader = new FeedDownloader();

$xml = $downloader->download();

$validator = new GetrixValidator();
$validator->validate($xml);

$parser = new GetrixParser();
$feed = $parser->parse($xml);

$mapper = new GetrixPropertyMapper();

$property = $mapper->map(
    $feed->properties[0]
);

$repository = new PropertyRepository();

$result = $repository->save($property);

$acfWriter = new PropertyAcfWriter();

$acfWriter->write(
    $result['post']->ID,
    $property
);

echo 'Import completed.' . PHP_EOL;
echo 'Post ID: ' . $result['post']->ID . PHP_EOL;
echo 'Getrix ID: ' . $property->getrixId . PHP_EOL;
echo 'Created: ' . ($result['created'] ? 'yes' : 'no') . PHP_EOL;
echo 'Updated: ' . ($result['updated'] ? 'yes' : 'no') . PHP_EOL;
