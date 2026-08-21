<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

final class GetrixFieldMap
{
    /**
     * Convert a Getrix field name to its ACF field name.
     */
    public function acfName(string $getrixField): string
    {
        return match ($getrixField) {
            'CodiceNazione' => 'codice_nazione',
            'CodiceComune' => 'codice_comune',
            'Comune' => 'comune',
            'Quartiere' => 'quartiere',
            'QuartiereZona' => 'quartiere_zona',
            'Localita' => 'localita',
            'Zona' => 'zona',
            'Strada' => 'strada',
            'Indirizzo' => 'indirizzo',
            'Civico' => 'civico',
            'PubblicaCivico' => 'pubblica_civico',
            'Cap' => 'cap',
            'PubblicaIndirizzo' => 'pubblica_indirizzo',
            'Latitudine' => 'latitudine',
            'Longitudine' => 'longitudine',
            'Zoom' => 'zoom',
            'PubblicaMappa' => 'pubblica_mappa',

            'Categoria' => 'categoria',
            'Contratto' => 'contratto',
            'Tipologia' => 'tipologia',
            'NrLocali' => 'nr_locali',
            'NrVani' => 'nr_vani',
            'Prezzo' => 'prezzo',
            'TrattativaRiservata' => 'trattativa_riservata',
            'MQSuperficie' => 'mq_superficie',
            'Riferimento' => 'riferimento',
            'SpeseMensili' => 'spese_mensili',
            'TipoSpese' => 'tipo_spese',
            'DurataContratto' => 'durata_contratto',
            'TipoProprieta' => 'tipo_proprieta',
            'SituazioneImmobile' => 'situazione_immobile',
            'Asta' => 'asta',
            'Pregio' => 'pregio',
            'Reddito' => 'reddito',

            'URLPlanimetria' => 'url_planimetria',
            'URLVirtualTour' => 'url_virtual_tour',
            'URLVisualTour' => 'url_visual_tour',
            'URLVideo' => 'url_video',

            'IDYouTube1' => 'id_youtube_1',
            'IDYouTube2' => 'id_youtube_2',
            'IDYouTube3' => 'id_youtube_3',
            'IDYouTube4' => 'id_youtube_4',

            'IDCantiereSoluzione' => 'id_cantiere_soluzione',
            'Permuta' => 'permuta',
            'AncheInAffitto' => 'anche_in_affitto',
            'PrezzoAffitto' => 'prezzo_affitto',
            'TrattativaRiservataAffitto' => 'trattativa_riservata_affitto',

            'DataInserimento' => 'data_inserimento',
            'DataModifica' => 'data_modifica',

            'Descrizioni' => 'descrizioni',
            'Residenziale' => 'residenziale',
            'Commerciale' => 'commerciale',
            'Attivita' => 'attivita',
            'Terreno' => 'terreno',
            'Vacanze' => 'vacanze',

            'Immagini' => 'immagini',
            'Allegati' => 'allegati',

            default => $this->snakeCase($getrixField),
        };
    }

    private function snakeCase(string $value): string
    {
        $value = preg_replace(
            '/(?<!^)[A-Z]/',
            '_$0',
            $value
        ) ?? $value;

        return strtolower($value);
    }
}
