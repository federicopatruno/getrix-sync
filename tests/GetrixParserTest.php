<?php

declare(strict_types=1);

use GetrixSync\Feed\GetrixParser;

require_once dirname(__DIR__) . '/src/Autoloader.php';

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'GetrixSync\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr(
            $class,
            strlen($prefix)
        );

        $file = dirname(__DIR__) . '/src/' .
            str_replace(
                '\\',
                '/',
                $relative
            ) .
            '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
);

$feedUrl = 'https://studiostilo.it/wp-content/feeds/B84A402B-0D84-4D26-BFDD-4EE8BB05605E.xml';

$xml = file_get_contents($feedUrl);

if ($xml === false) {
    throw new RuntimeException(
        'Unable to download Getrix feed.'
    );
}

$parser = new GetrixParser();

$feed = $parser->parse($xml);

echo PHP_EOL;
echo "===== GETRIX PARSER TEST =====" . PHP_EOL;
echo PHP_EOL;

echo 'Version: ' . $feed->version . PHP_EOL;
echo 'User: ' . $feed->user . PHP_EOL;
echo 'Properties: ' . count($feed->properties) . PHP_EOL;
echo PHP_EOL;

if ($feed->properties === []) {
    throw new RuntimeException(
        'Getrix feed contains no properties.'
    );
}

$property = $feed->properties[0];

echo "===== FIRST PROPERTY =====" . PHP_EOL;
echo PHP_EOL;

print_r($property);

echo PHP_EOL;
echo "===== IMPORTANT FIELDS =====" . PHP_EOL;
echo PHP_EOL;

$fields = [
    'getrix_id',
    'codice_nazione',
    'codice_comune',
    'comune',
    'strada',
    'strada_id',
    'indirizzo',
    'civico',
    'pubblica_civico',
    'pubblica_indirizzo',
    'latitudine',
    'longitudine',
    'zoom',
    'pubblica_mappa',
    'categoria',
    'contratto',
    'tipologia',
    'tipologia_id',
    'nr_locali',
    'nr_vani',
    'prezzo',
    'trattativa_riservata',
    'mq_superficie',
    'tipo_spese',
    'tipo_proprieta',
    'id_youtube_1',
    'data_inserimento',
    'data_modifica',
    'descrizioni',
    'commerciale',
    'immagini',
];

foreach ($fields as $field) {
    $value = $property[$field] ?? null;

    echo str_pad(
        $field,
        28
    );

    if (is_array($value)) {
        echo '[array]' . PHP_EOL;
        continue;
    }

    if (is_bool($value)) {
        echo $value ? 'true' : 'false';
        echo PHP_EOL;
        continue;
    }

    if ($value === null) {
        echo '[NULL]' . PHP_EOL;
        continue;
    }

    echo $value . PHP_EOL;
}

echo PHP_EOL;
echo "===== STATUS =====" . PHP_EOL;
echo "OK" . PHP_EOL;
