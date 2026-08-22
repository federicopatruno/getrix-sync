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

        $version = trim(
            (string) ($document['Versione'] ?? '')
        );

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
            $properties[] = $this->parseProperty(
                $property
            );
        }

        return new GetrixFeed(
            version: $version,
            user: (string) ($document['User'] ?? ''),
            properties: $properties,
        );
    }

    /**
     * Parse one Immobile node.
     *
     * The XML structure is converted into the same snake_case
     * naming convention used by the ACF structure builder.
     *
     * @return array<string, mixed>
     */
    private function parseProperty(
        SimpleXMLElement $property
    ): array {
        $result = [];

        /*
         * IDImmobile is an XML attribute and is therefore handled
         * explicitly because it is the canonical Getrix identifier.
         */
        $result['getrix_id'] = trim(
            (string) ($property['IDImmobile'] ?? '')
        );

        /*
         * A field name is only turned into a list when it actually
         * occurs more than once. Counting occurrences up-front (as
         * opposed to inspecting the shape of the first parsed value)
         * is required because a *single* repeated element can itself
         * parse to an array (e.g. an element with attributes or
         * children), which would otherwise be indistinguishable from
         * "already collected into a list".
         */
        $counts = $this->childNameCounts(
            $property->children()
        );

        foreach ($property->children() as $element) {
            $fieldName = $this->fieldName(
                $element->getName()
            );

            $value = $this->parseElement(
                $element
            );

            /*
             * Some XML elements contain an ID attribute in addition
             * to their textual value. Preserve that information using
             * the same naming convention already used by the project.
             */
            $attributes = $this->parseAttributes(
                $element
            );

            if ($attributes !== []) {
                $this->mergeElementAttributes(
                    $result,
                    $fieldName,
                    $attributes
                );
            }

            if (($counts[$fieldName] ?? 1) > 1) {
                $result[$fieldName][] = $value;
                continue;
            }

            $result[$fieldName] = $value;
        }

        return $result;
    }

    /**
     * Recursively parse an XML element.
     */
    private function parseElement(
        SimpleXMLElement $element
    ): mixed {
        $children = $element->children();

        /*
         * Leaf element.
         */
        if ($children->count() === 0) {
            return $this->scalarValue(
                $element
            );
        }

        $counts = $this->childNameCounts(
            $children
        );

        $result = [];

        foreach ($children as $child) {
            $childName = $this->fieldName(
                $child->getName()
            );

            $childValue = $this->parseElement(
                $child
            );

            /*
             * Preserve repeated elements. A field name is only
             * treated as a list when it genuinely occurs more than
             * once; see the note in parseProperty() for why we
             * cannot rely on is_array($result[$childName]) here.
             */
            if (($counts[$childName] ?? 1) > 1) {
                $result[$childName][] = $childValue;
                continue;
            }

            $result[$childName] = $childValue;
        }

        /*
         * If the element has attributes, flatten them directly into
         * this element's own result (e.g. <Immagine IDImmagine="1"
         * Tipo="F"> -> ['id_immagine' => 1, 'tipo' => 'F', ...]),
         * so the mapper can address them without knowing about the
         * top-level "<Elemento>_<attributo>" convention used by
         * parseProperty().
         */
        $attributes = $this->parseAttributes(
            $element
        );

        if ($attributes !== []) {
            $this->mergeOwnAttributes(
                $result,
                $attributes
            );
        }

        return $result;
    }

    /**
     * Count how many times each (already snake_cased) child element
     * name occurs among a node's direct children.
     *
     * @return array<string, int>
     */
    private function childNameCounts(
        SimpleXMLElement $children
    ): array {
        $counts = [];

        foreach ($children as $child) {
            $name = $this->fieldName(
                $child->getName()
            );

            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Convert a leaf XML value to a sensible PHP scalar.
     */
    private function scalarValue(
        SimpleXMLElement $element
    ): mixed {
        $value = trim(
            (string) $element
        );

        if ($value === '') {
            return null;
        }

        /*
         * Boolean values used by Getrix.
         */
        if (
            strcasecmp($value, 'true') === 0 ||
            strcasecmp($value, 'false') === 0
        ) {
            return strcasecmp(
                $value,
                'true'
            ) === 0;
        }

        /*
         * Integer values.
         */
        if (
            preg_match(
                '/^-?\d+$/',
                $value
            ) === 1
        ) {
            return (int) $value;
        }

        /*
         * Decimal values.
         */
        if (
            preg_match(
                '/^-?\d+\.\d+$/',
                $value
            ) === 1
        ) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * Convert an XML element name to the project's snake_case field name.
     *
     * Examples:
     *
     * MQSuperficie        -> mq_superficie
     * IDYouTube1          -> id_you_tube_1
     * IDCantiereSoluzione -> id_cantiere_soluzione
     * DataInserimento     -> data_inserimento
     */
    private function fieldName(
        string $name
    ): string {
        /*
         * Known Getrix acronyms/compound names.
         *
         * These are handled before the generic CamelCase conversion
         * to keep the generated names aligned with the ACF builder.
         */
        $special = [
            'MQSuperficie' => 'mq_superficie',

            'IDYouTube1' => 'id_youtube_1',
            'IDYouTube2' => 'id_youtube_2',
            'IDYouTube3' => 'id_youtube_3',
            'IDYouTube4' => 'id_youtube_4',

            'IDCantiereSoluzione' =>
            'id_cantiere_soluzione',

            'IDImmobile' => 'getrix_id',

            'IDStrada' => 'id_strada',
            'IDTipologia' => 'id_tipologia',
        ];

        if (isset($special[$name])) {
            return $special[$name];
        }

        /*
         * Insert underscores between acronym boundaries.
         */
        $name = preg_replace(
            '/([A-Z]+)([A-Z][a-z])/',
            '$1_$2',
            $name
        ) ?? $name;

        /*
         * Insert underscores between lower-case/digit and uppercase.
         */
        $name = preg_replace(
            '/([a-z0-9])([A-Z])/',
            '$1_$2',
            $name
        ) ?? $name;

        /*
         * Separate trailing numeric suffixes:
         *
         * YouTube1 -> YouTube_1
         */
        $name = preg_replace(
            '/([A-Za-z])(\d+)$/',
            '$1_$2',
            $name
        ) ?? $name;

        return strtolower(
            trim($name, '_')
        );
    }

    /**
     * Read XML attributes.
     *
     * @return array<string, string>
     */
    private function parseAttributes(
        SimpleXMLElement $element
    ): array {
        $attributes = [];

        foreach ($element->attributes() as $name => $value) {
            $attributes[(string) $name] = trim(
                (string) $value
            );
        }

        return $attributes;
    }

    /**
     * Promote relevant XML attributes to the same flat naming
     * convention used by the existing parser.
     *
     * Example:
     *
     * <Strada IDStrada="56">viale</Strada>
     *
     * becomes:
     *
     * strada => "viale"
     * strada_id => "56"
     */
    private function mergeElementAttributes(
        array &$result,
        string $fieldName,
        array $attributes
    ): void {
        foreach ($attributes as $name => $value) {
            $attributeName = $this->fieldName(
                $name
            );

            /*
             * IDStrada -> strada_id
             * IDTipologia -> tipologia_id
             */
            if (
                str_starts_with(
                    $attributeName,
                    'id_'
                )
            ) {
                $suffix = substr(
                    $attributeName,
                    3
                );

                $result[$suffix . '_id'] = $this->castAttribute(
                    $value
                );

                continue;
            }

            $result[$fieldName . '_' . $attributeName] = $this->castAttribute(
                $value
            );
        }
    }

    /**
     * Flatten an element's own attributes directly into its parsed
     * result, using their plain snake_case name (no prefix).
     *
     * Example:
     *
     * <Immagine IDImmagine="1" Tipo="F">...</Immagine>
     *
     * merges 'id_immagine' => 1 and 'tipo' => 'F' into the Immagine's
     * own result array.
     *
     * @param array<string, mixed> $result
     * @param array<string, string> $attributes
     */
    private function mergeOwnAttributes(
        array &$result,
        array $attributes
    ): void {
        foreach ($attributes as $name => $value) {
            $attributeName = $this->fieldName(
                $name
            );

            $result[$attributeName] = $this->castAttribute(
                $value
            );
        }
    }

    /**
     * Cast XML attributes where possible.
     */
    private function castAttribute(
        string $value
    ): mixed {
        if (
            preg_match(
                '/^-?\d+$/',
                $value
            ) === 1
        ) {
            return (int) $value;
        }

        if (
            preg_match(
                '/^-?\d+\.\d+$/',
                $value
            ) === 1
        ) {
            return (float) $value;
        }

        if (
            strcasecmp($value, 'true') === 0
        ) {
            return true;
        }

        if (
            strcasecmp($value, 'false') === 0
        ) {
            return false;
        }

        return $value;
    }

    /**
     * @return string
     */
    private function formatXmlErrors(): string
    {
        $errors = [];

        foreach (libxml_get_errors() as $error) {
            $errors[] = trim(
                $error->message
            );
        }

        libxml_clear_errors();

        if ($errors === []) {
            return 'Unable to parse Getrix XML.';
        }

        return 'Unable to parse Getrix XML: ' .
            implode('; ', $errors);
    }
}
