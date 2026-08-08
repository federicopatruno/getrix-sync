<?php

declare(strict_types=1);

namespace GetrixSync\Feed;

use RuntimeException;
use SimpleXMLElement;

final class GetrixParser
{
    /**
     * Parse a Getrix 3.1 XML document.
     */
    public function parse(string $xml): GetrixFeed
    {
        if (trim($xml) === '') {
            throw new RuntimeException(
                'Cannot parse an empty XML document.'
            );
        }

        libxml_use_internal_errors(true);

        $document = simplexml_load_string(
            $xml,
            SimpleXMLElement::class,
            LIBXML_NONET | LIBXML_NOBLANKS
        );

        if ($document === false) {
            throw new RuntimeException(
                $this->formatXmlErrors()
            );
        }

        if ($document->getName() !== 'Getrix') {
            throw new RuntimeException(
                sprintf(
                    'Invalid Getrix document root: "%s".',
                    $document->getName()
                )
            );
        }

        $version = trim((string) ($document['Versione'] ?? ''));

        if ($version === '') {
            throw new RuntimeException(
                'Getrix feed version is missing.'
            );
        }

        if ($version !== '3.1.0') {
            throw new RuntimeException(
                sprintf(
                    'Unsupported Getrix feed version: "%s".',
                    $version
                )
            );
        }

        $properties = [];

        foreach ($document->Immobile as $property) {
            $properties[] = $this->parseProperty($property);
        }

        return new GetrixFeed(
            version: $version,
            user: (string) ($document['User'] ?? ''),
            properties: $properties,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function parseProperty(
        SimpleXMLElement $property
    ): array {
        $commercial = $property->Commerciale ?? null;

        return [
            'getrix_id' => (string) ($property['IDImmobile'] ?? ''),

            'codice_nazione' => $this->string(
                $property->CodiceNazione
            ),

            'codice_comune' => $this->string(
                $property->CodiceComune
            ),

            'comune' => $this->string(
                $property->Comune
            ),

            'quartiere_zona_id' => $this->attribute(
                $property->QuartiereZona ?? null,
                'CodiceQuartiereZona'
            ),

            'zona' => $this->string(
                $property->Zona
            ),

            'strada_tipo' => $this->string(
                $property->Strada
            ),

            'strada_id' => $this->attribute(
                $property->Strada ?? null,
                'IDStrada'
            ),

            'indirizzo' => $this->string(
                $property->Indirizzo
            ),

            'civico' => $this->string(
                $property->Civico
            ),

            'pubblica_civico' => $this->bool(
                $property->PubblicaCivico
            ),

            'cap' => $this->string(
                $property->Cap
            ),

            'pubblica_indirizzo' => $this->bool(
                $property->PubblicaIndirizzo
            ),

            'latitudine' => $this->float(
                $property->Latitudine
            ),

            'longitudine' => $this->float(
                $property->Longitudine
            ),

            'zoom' => $this->int(
                $property->Zoom
            ),

            'pubblica_mappa' => $this->bool(
                $property->PubblicaMappa
            ),

            'categoria' => $this->string(
                $property->Categoria
            ),

            'contratto' => $this->string(
                $property->Contratto
            ),

            'tipologia_id' => $this->attribute(
                $property->Tipologia ?? null,
                'IDTipologia'
            ),

            'tipologia' => $this->string(
                $property->Tipologia
            ),

            'nr_locali' => $this->int(
                $property->NrLocali
            ),

            'prezzo' => $this->float(
                $property->Prezzo
            ),

            'trattativa_riservata' => $this->bool(
                $property->TrattativaRiservata
            ),

            'mq_superficie' => $this->float(
                $property->MQSuperficie
            ),

            'tipo_spese' => $this->string(
                $property->TipoSpese
            ),

            'tipo_proprieta' => $this->string(
                $property->TipoProprieta
            ),

            'id_youtube_1' => $this->string(
                $property->IDYouTube1
            ),

            'data_inserimento' => $this->dateTime(
                $property->DataInserimento
            ),

            'data_modifica' => $this->dateTime(
                $property->DataModifica
            ),

            'descrizioni' => $this->parseDescriptions(
                $property->Descrizioni ?? null
            ),

            'commerciale' => $this->parseCommercial(
                $commercial
            ),

            'immagini' => $this->parseImages(
                $property->Immagini ?? null
            ),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseDescriptions(
        ?SimpleXMLElement $descriptions
    ): array {
        if ($descriptions === null) {
            return [];
        }

        $result = [];

        foreach ($descriptions->Descrizione as $description) {
            $result[] = [
                'lingua' => $this->attribute(
                    $description,
                    'Lingua'
                ),

                'titolo' => $this->string(
                    $description->Titolo
                ),

                'testo' => $this->string(
                    $description->Testo
                ),

                'testo_breve' => $this->string(
                    $description->TestoBreve
                ),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCommercial(
        ?SimpleXMLElement $commercial
    ): array {
        if ($commercial === null) {
            return [];
        }

        return [
            'tipologia_uso' => $this->string(
                $commercial->TipologiaUso
            ),

            'stato_manutenzione' => $this->string(
                $commercial->StatoManutenzione
            ),

            'categoria_catastale' => $this->string(
                $commercial->CategoriaCatastale
            ),

            'stato_immobile' => $this->string(
                $commercial->StatoImmobile
            ),

            'piani_edificio' => $this->int(
                $commercial->PianiEdificio
            ),

            'riscaldamento' => $this->string(
                $commercial->Riscaldamento
            ),

            'cablaggio_rete' => $this->bool(
                $commercial->CablaggioRete
            ),

            'aria_condizionata' => $this->bool(
                $commercial->AriaCondizionata
            ),

            'nr_servizi_igiene' => $this->int(
                $commercial->NrServiziIgiene
            ),

            'ascensore' => $this->bool(
                $commercial->Ascensore
            ),

            'portineria' => $this->bool(
                $commercial->Portineria
            ),

            'nuova_costruzione' => $this->bool(
                $commercial->NuovaCostruzione
            ),

            'legge_classe_energetica' => $this->string(
                $commercial->LeggeClasseEnergetica
            ),

            'classe_energetica' => $this->string(
                $commercial->ClasseEnergetica
            ),

            'ipe' => $this->float(
                $commercial->IPE
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseImages(
        ?SimpleXMLElement $images
    ): array {
        if ($images === null) {
            return [];
        }

        $result = [];

        foreach ($images->Immagine as $image) {
            $result[] = [
                'id' => $this->attribute(
                    $image,
                    'IDImmagine'
                ),

                'tipo' => $this->attribute(
                    $image,
                    'Tipo'
                ),

                'url' => $this->string(
                    $image->URL
                ),

                'data_modifica' => $this->dateTime(
                    $image->DataModifica
                ),

                'titolo' => $this->string(
                    $image->Titolo
                ),

                'posizione' => $this->int(
                    $image->Posizione
                ),
            ];
        }

        return $result;
    }

    private function string(
        ?SimpleXMLElement $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $result = trim((string) $value);

        return $result === '' ? null : $result;
    }

    private function attribute(
        ?SimpleXMLElement $element,
        string $attribute
    ): ?string {
        if ($element === null) {
            return null;
        }

        if (!isset($element[$attribute])) {
            return null;
        }

        $value = trim((string) $element[$attribute]);

        return $value === '' ? null : $value;
    }

    private function bool(
        ?SimpleXMLElement $value
    ): ?bool {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
    }

    private function int(
        ?SimpleXMLElement $value
    ): ?int {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_INT,
            FILTER_NULL_ON_FAILURE
        );
    }

    private function float(
        ?SimpleXMLElement $value
    ): ?float {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function dateTime(
        ?SimpleXMLElement $value
    ): ?string {
        $value = $this->string($value);

        if ($value === null) {
            return null;
        }

        try {
            return (
                new \DateTimeImmutable($value)
            )->format('Y-m-d H:i:s');
        } catch (\DateMalformedStringException) {
            return null;
        }
    }

    private function formatXmlErrors(): string
    {
        $errors = libxml_get_errors();

        libxml_clear_errors();

        if ($errors === []) {
            return 'Unable to parse Getrix XML feed.';
        }

        $messages = array_map(
            static fn(\LibXMLError $error): string => trim(
                $error->message
            ),
            $errors
        );

        return 'Unable to parse Getrix XML feed: '
            . implode('; ', $messages);
    }
}
