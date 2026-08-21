<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Autoloader.php';

use GetrixSync\Domain\GetrixPropertyMapper;

$property = [
    'getrix_id' => '192985687',

    'codice_nazione' => 'IT',
    'codice_comune' => '108001',
    'comune' => 'Agrate Brianza',

    'strada' => 'viale',
    'strada_id' => '56',
    'indirizzo' => 'Gian Bartolomeo Colleoni',
    'civico' => null,

    'pubblica_civico' => false,
    'pubblica_indirizzo' => true,

    'latitudine' => '45.57300186',
    'longitudine' => '9.33699989',
    'zoom' => 12,
    'pubblica_mappa' => true,

    'categoria' => '2',
    'contratto' => 'V',
    'tipologia' => 'Ufficio',
    'tipologia_id' => '21',

    'nr_locali' => '6',
    'nr_vani' => null,

    'prezzo' => '770000',
    'trattativa_riservata' => false,
    'mq_superficie' => '622',

    'tipo_spese' => '0',
    'tipo_proprieta' => null,

    'id_youtube_1' => 'oEZ1eua37l0',

    'data_inserimento' => '2025-04-28T13:30:10',
    'data_modifica' => '2025-04-28T13:31:07',

    'descrizioni' => [
        [
            'titolo' => 'Ufficio in vendita',
            'testo' => 'Descrizione immobile di test.',
            'testo_breve' => 'Ufficio ad Agrate Brianza.',
        ],
    ],

    'commerciale' => [
        'test' => 'commerciale',
    ],

    'immagini' => [
        [
            'url' => 'https://example.com/test.jpg',
        ],
    ],
];

$mapper = new GetrixPropertyMapper();

$result = $mapper->map($property);

echo "===== GETRIX PROPERTY MAPPER TEST =====\n\n";

echo 'Getrix ID: ';
var_dump($result->getrixId);

echo "\n===== DATA =====\n";

$fields = [
    'codice_nazione',
    'codice_comune',
    'comune',
    'categoria',
    'contratto',
    'tipologia',
    'tipologia_id',
    'nr_locali',
    'prezzo',
    'mq_superficie',
    'id_youtube_1',
    'data_inserimento',
    'data_modifica',
];

foreach ($fields as $field) {
    printf(
        "%-25s",
        $field
    );

    var_dump(
        $result->code($field)
    );
}

echo "\n===== DESCRIPTIONS =====\n";

var_dump($result->descriptions);

echo "\n===== COMMERCIAL =====\n";

var_dump($result->commercial);

echo "\n===== IMAGES =====\n";

var_dump($result->images);

echo "\n===== TITLE =====\n";

var_dump($result->title());

echo "\n===== DESCRIPTION =====\n";

var_dump($result->description());

echo "\n===== SHORT DESCRIPTION =====\n";

var_dump($result->shortDescription());

echo "\n===== STATUS =====\n";
echo "OK\n";
