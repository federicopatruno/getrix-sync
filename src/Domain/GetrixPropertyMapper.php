<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

use RuntimeException;

final class GetrixPropertyMapper
{
    /**
     * @param array<string, mixed> $property
     */
    public function map(array $property): Property
    {
        $getrixId = trim(
            (string) ($property['getrix_id'] ?? '')
        );

        if ($getrixId === '') {
            throw new RuntimeException(
                'Cannot map a Getrix property without IDImmobile.'
            );
        }

        return new Property(
            getrixId: $getrixId,

            data: [
                'codice_nazione' => $property['codice_nazione'] ?? null,
                'codice_comune' => $property['codice_comune'] ?? null,
                'comune' => $property['comune'] ?? null,
                'quartiere_zona_id' => $property['quartiere_zona_id'] ?? null,
                'zona' => $property['zona'] ?? null,
                'strada_tipo' => $property['strada_tipo'] ?? null,
                'strada_id' => $property['strada_id'] ?? null,
                'indirizzo' => $property['indirizzo'] ?? null,
                'civico' => $property['civico'] ?? null,
                'pubblica_civico' => $property['pubblica_civico'] ?? null,
                'cap' => $property['cap'] ?? null,
                'pubblica_indirizzo' => $property['pubblica_indirizzo'] ?? null,
                'latitudine' => $property['latitudine'] ?? null,
                'longitudine' => $property['longitudine'] ?? null,
                'zoom' => $property['zoom'] ?? null,
                'pubblica_mappa' => $property['pubblica_mappa'] ?? null,
                'categoria' => $property['categoria'] ?? null,
                'contratto' => $property['contratto'] ?? null,
                'tipologia_id' => $property['tipologia_id'] ?? null,
                'tipologia' => $property['tipologia'] ?? null,
                'nr_locali' => $property['nr_locali'] ?? null,
                'prezzo' => $property['prezzo'] ?? null,
                'trattativa_riservata' => $property['trattativa_riservata'] ?? null,
                'mq_superficie' => $property['mq_superficie'] ?? null,
                'tipo_spese' => $property['tipo_spese'] ?? null,
                'tipo_proprieta' => $property['tipo_proprieta'] ?? null,
                'id_youtube_1' => $property['id_youtube_1'] ?? null,
                'data_inserimento' => $property['data_inserimento'] ?? null,
                'data_modifica' => $property['data_modifica'] ?? null,
            ],

            descriptions: $property['descrizioni'] ?? [],

            commercial: $property['commerciale'] ?? [],

            images: $property['immagini'] ?? [],
        );
    }
}
