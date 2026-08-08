<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;

final class PropertyFields implements ServiceProvider
{
    private const GROUP_KEY = 'group_getrix_immobile';

    public function register(Container $container): void
    {
        // ACF is an external WordPress dependency.
    }

    public function boot(Container $container): void
    {
        add_action('acf/init', [$this, 'registerFields']);
    }

    public function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => self::GROUP_KEY,
            'title' => 'Dati Immobile Getrix',
            'fields' => $this->fields(),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => Config::get('post_type', 'immobile'),
                    ],
                ],
            ],
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'field',
            'hide_on_screen' => [
                'the_content',
            ],
            'active' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fields(): array
    {
        return [
            $this->tab(
                'tab_identificazione',
                'Identificazione'
            ),

            $this->text(
                'field_getrix_id',
                'getrix_id',
                'ID Immobile',
                'Identificativo univoco dell\'immobile nel feed Getrix.',
                true
            ),

            $this->text(
                'field_codice_nazione',
                'codice_nazione',
                'Codice Nazione'
            ),

            $this->text(
                'field_codice_comune',
                'codice_comune',
                'Codice Comune'
            ),

            $this->text(
                'field_categoria',
                'categoria',
                'Categoria'
            ),

            $this->text(
                'field_tipologia_id',
                'tipologia_id',
                'ID Tipologia'
            ),

            $this->text(
                'field_tipologia',
                'tipologia',
                'Tipologia'
            ),

            $this->tab(
                'tab_localizzazione',
                'Localizzazione'
            ),

            $this->text(
                'field_comune',
                'comune',
                'Comune'
            ),

            $this->text(
                'field_quartiere_zona_id',
                'quartiere_zona_id',
                'ID Quartiere / Zona'
            ),

            $this->text(
                'field_zona',
                'zona',
                'Zona'
            ),

            $this->text(
                'field_strada_tipo',
                'strada_tipo',
                'Tipo Strada'
            ),

            $this->text(
                'field_strada_id',
                'strada_id',
                'ID Strada'
            ),

            $this->text(
                'field_indirizzo',
                'indirizzo',
                'Indirizzo'
            ),

            $this->text(
                'field_civico',
                'civico',
                'Civico'
            ),

            $this->trueFalse(
                'field_pubblica_civico',
                'pubblica_civico',
                'Pubblica Civico'
            ),

            $this->text(
                'field_cap',
                'cap',
                'CAP'
            ),

            $this->trueFalse(
                'field_pubblica_indirizzo',
                'pubblica_indirizzo',
                'Pubblica Indirizzo'
            ),

            $this->number(
                'field_latitudine',
                'latitudine',
                'Latitudine'
            ),

            $this->number(
                'field_longitudine',
                'longitudine',
                'Longitudine'
            ),

            $this->number(
                'field_zoom',
                'zoom',
                'Zoom',
                0,
                30
            ),

            $this->trueFalse(
                'field_pubblica_mappa',
                'pubblica_mappa',
                'Pubblica Mappa'
            ),

            $this->tab(
                'tab_commerciale',
                'Commerciale'
            ),

            $this->text(
                'field_contratto',
                'contratto',
                'Contratto'
            ),

            $this->number(
                'field_nr_locali',
                'nr_locali',
                'Numero Locali',
                0
            ),

            $this->number(
                'field_prezzo',
                'prezzo',
                'Prezzo',
                0,
                null,
                2
            ),

            $this->trueFalse(
                'field_trattativa_riservata',
                'trattativa_riservata',
                'Trattativa Riservata'
            ),

            $this->number(
                'field_mq_superficie',
                'mq_superficie',
                'Superficie (mq)',
                0,
                null,
                2
            ),

            $this->text(
                'field_tipo_spese',
                'tipo_spese',
                'Tipo Spese'
            ),

            $this->text(
                'field_tipo_proprieta',
                'tipo_proprieta',
                'Tipo Proprietà'
            ),

            $this->text(
                'field_id_youtube_1',
                'id_youtube_1',
                'YouTube ID'
            ),

            $this->tab(
                'tab_date',
                'Date'
            ),

            $this->dateTime(
                'field_data_inserimento',
                'data_inserimento',
                'Data Inserimento'
            ),

            $this->dateTime(
                'field_data_modifica',
                'data_modifica',
                'Data Modifica'
            ),

            $this->tab(
                'tab_descrizione',
                'Descrizione'
            ),

            $this->text(
                'field_descrizione_titolo',
                'descrizione_titolo',
                'Titolo'
            ),

            $this->wysiwyg(
                'field_descrizione_testo',
                'descrizione_testo',
                'Descrizione'
            ),

            $this->textarea(
                'field_descrizione_breve',
                'descrizione_breve',
                'Descrizione Breve'
            ),

            $this->tab(
                'tab_caratteristiche',
                'Caratteristiche'
            ),

            $this->text(
                'field_tipo_costruzione',
                'tipo_costruzione',
                'Tipo Costruzione'
            ),

            $this->text(
                'field_tipologia_uso',
                'tipologia_uso',
                'Tipologia Uso'
            ),

            $this->text(
                'field_stato_manutenzione',
                'stato_manutenzione',
                'Stato Manutenzione'
            ),

            $this->text(
                'field_categoria_catastale',
                'categoria_catastale',
                'Categoria Catastale'
            ),

            $this->text(
                'field_stato_immobile',
                'stato_immobile',
                'Stato Immobile'
            ),

            $this->number(
                'field_piani_edificio',
                'piani_edificio',
                'Piani Edificio',
                0
            ),

            $this->text(
                'field_riscaldamento',
                'riscaldamento',
                'Riscaldamento'
            ),

            $this->trueFalse(
                'field_cablaggio_rete',
                'cablaggio_rete',
                'Cablaggio Rete'
            ),

            $this->trueFalse(
                'field_aria_condizionata',
                'aria_condizionata',
                'Aria Condizionata'
            ),

            $this->number(
                'field_nr_servizi_igiene',
                'nr_servizi_igiene',
                'Numero Servizi Igienici',
                0
            ),

            $this->trueFalse(
                'field_ascensore',
                'ascensore',
                'Ascensore'
            ),

            $this->trueFalse(
                'field_portineria',
                'portineria',
                'Portineria'
            ),

            $this->trueFalse(
                'field_nuova_costruzione',
                'nuova_costruzione',
                'Nuova Costruzione'
            ),

            $this->tab(
                'tab_energia',
                'Energia'
            ),

            $this->text(
                'field_legge_classe_energetica',
                'legge_classe_energetica',
                'Legge Classe Energetica'
            ),

            $this->text(
                'field_classe_energetica',
                'classe_energetica',
                'Classe Energetica'
            ),

            $this->number(
                'field_ipe',
                'ipe',
                'IPE',
                0,
                null,
                2
            ),

            $this->tab(
                'tab_immagini',
                'Immagini'
            ),

            [
                'key' => 'field_immagini',
                'label' => 'Immagini Getrix',
                'name' => 'immagini_getrix',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Aggiungi immagine',
                'sub_fields' => [
                    $this->text(
                        'field_immagine_id',
                        'id_immagine',
                        'ID Immagine',
                        null,
                        true
                    ),

                    $this->text(
                        'field_immagine_tipo',
                        'tipo',
                        'Tipo'
                    ),

                    $this->url(
                        'field_immagine_url',
                        'url',
                        'URL'
                    ),

                    $this->dateTime(
                        'field_immagine_data_modifica',
                        'data_modifica',
                        'Data Modifica'
                    ),

                    $this->text(
                        'field_immagine_titolo',
                        'titolo',
                        'Titolo'
                    ),

                    $this->number(
                        'field_immagine_posizione',
                        'posizione',
                        'Posizione',
                        0
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tab(string $key, string $label): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'name' => '',
            'type' => 'tab',
            'placement' => 'top',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function text(
        string $key,
        string $name,
        string $label,
        ?string $instructions = null,
        bool $readonly = false
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'text',
            'instructions' => $instructions,
            'required' => false,
            'readonly' => $readonly,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function textarea(
        string $key,
        string $name,
        string $label
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'textarea',
            'rows' => 5,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function wysiwyg(
        string $key,
        string $name,
        string $label
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'wysiwyg',
            'tabs' => 'all',
            'toolbar' => 'basic',
            'media_upload' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function number(
        string $key,
        string $name,
        string $label,
        ?float $min = null,
        ?float $max = null,
        ?int $decimals = null
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'number',
            'required' => false,
        ];

        if ($min !== null) {
            $field['min'] = $min;
        }

        if ($max !== null) {
            $field['max'] = $max;
        }

        if ($decimals !== null) {
            $field['step'] = $decimals === 0
                ? 1
                : 1 / (10 ** $decimals);
        }

        return $field;
    }

    /**
     * @return array<string, mixed>
     */
    private function trueFalse(
        string $key,
        string $name,
        string $label
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'true_false',
            'ui' => true,
            'default_value' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dateTime(
        string $key,
        string $name,
        string $label
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'date_time_picker',
            'display_format' => 'd/m/Y H:i:s',
            'return_format' => 'Y-m-d H:i:s',
            'first_day' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function url(
        string $key,
        string $name,
        string $label
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'url',
        ];
    }
}
