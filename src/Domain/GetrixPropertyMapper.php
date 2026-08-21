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

            data: $this->mapData($property),

            descriptions: $this->mapArray(
                $property['descrizioni'] ?? []
            ),

            commercial: $this->mapArray(
                $property['commerciale'] ?? []
            ),

            images: $this->mapArray(
                $property['immagini'] ?? []
            ),
        );
    }

    /**
     * @param array<string, mixed> $property
     * @return array<string, mixed>
     */
    private function mapData(array $property): array
    {
        return [
            /*
             * Identificazione
             */
            'codice_nazione' => $property['codice_nazione'] ?? null,
            'codice_comune' => $property['codice_comune'] ?? null,
            'comune' => $property['comune'] ?? null,

            /*
             * Localizzazione
             */
            'quartiere_zona' => $property['quartiere_zona'] ?? null,
            'localita' => $property['localita'] ?? null,
            'zona' => $property['zona'] ?? null,
            'strada' => $property['strada'] ?? null,
            'strada_id' => $property['strada_id'] ?? null,
            'indirizzo' => $property['indirizzo'] ?? null,
            'civico' => $property['civico'] ?? null,
            'pubblica_civico' => $property['pubblica_civico'] ?? null,
            'cap' => $property['cap'] ?? null,
            'pubblica_indirizzo' => $property['pubblica_indirizzo'] ?? null,

            /*
             * Mappa
             */
            'latitudine' => $property['latitudine'] ?? null,
            'longitudine' => $property['longitudine'] ?? null,
            'zoom' => $property['zoom'] ?? null,
            'pubblica_mappa' => $property['pubblica_mappa'] ?? null,

            /*
             * Informazioni principali
             */
            'categoria' => $property['categoria'] ?? null,
            'contratto' => $property['contratto'] ?? null,
            'tipologia' => $property['tipologia'] ?? null,
            'tipologia_id' => $property['tipologia_id'] ?? null,
            'nr_locali' => $property['nr_locali'] ?? null,
            'nr_vani' => $property['nr_vani'] ?? null,
            'prezzo' => $property['prezzo'] ?? null,
            'trattativa_riservata' => $property['trattativa_riservata'] ?? null,
            'mq_superficie' => $property['mq_superficie'] ?? null,

            /*
             * Spese / proprietà
             */
            'spese_mensili' => $property['spese_mensili'] ?? null,
            'tipo_spese' => $property['tipo_spese'] ?? null,
            'durata_contratto' => $property['durata_contratto'] ?? null,
            'tipo_proprieta' => $property['tipo_proprieta'] ?? null,
            'situazione_immobile' => $property['situazione_immobile'] ?? null,

            /*
             * Stato / caratteristiche
             */
            'asta' => $property['asta'] ?? null,
            'pregio' => $property['pregio'] ?? null,
            'reddito' => $property['reddito'] ?? null,
            'permuta' => $property['permuta'] ?? null,
            'anche_in_affitto' => $property['anche_in_affitto'] ?? null,

            /*
             * Affitto
             */
            'prezzo_affitto' => $property['prezzo_affitto'] ?? null,
            'trattativa_riservata_affitto' =>
            $property['trattativa_riservata_affitto'] ?? null,

            /*
             * Riferimenti / media
             */
            'riferimento' => $property['riferimento'] ?? null,
            'url_planimetria' => $property['url_planimetria'] ?? null,
            'url_virtual_tour' => $property['url_virtual_tour'] ?? null,
            'url_visual_tour' => $property['url_visual_tour'] ?? null,
            'url_video' => $property['url_video'] ?? null,

            'id_youtube_1' => $property['id_youtube_1'] ?? null,
            'id_youtube_2' => $property['id_youtube_2'] ?? null,
            'id_youtube_3' => $property['id_youtube_3'] ?? null,
            'id_youtube_4' => $property['id_youtube_4'] ?? null,

            /*
             * Cantiere
             */
            'id_cantiere_soluzione' =>
            $property['id_cantiere_soluzione'] ?? null,

            /*
             * Date
             */
            'data_inserimento' =>
            $property['data_inserimento'] ?? null,

            'data_modifica' =>
            $property['data_modifica'] ?? null,
        ];
    }

    /**
     * @param mixed $value
     * @return array<int|string, mixed>
     */
    private function mapArray(mixed $value): array
    {
        return is_array($value)
            ? $value
            : [];
    }
}
