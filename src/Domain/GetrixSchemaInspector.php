<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

use RuntimeException;
use SimpleXMLElement;

final class GetrixSchemaInspector
{
    private const XS_NAMESPACE =
    'http://www.w3.org/2001/XMLSchema';

    private SimpleXMLElement $schema;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $cache = null;

    public function __construct(
        private readonly string $schemaPath
    ) {
        $this->schema = $this->loadSchema();

        $this->schema->registerXPathNamespace(
            'xs',
            self::XS_NAMESPACE
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $root = $this->findElement('Getrix');

        if ($root === null) {
            throw new RuntimeException(
                'Getrix root element not found in XSD.'
            );
        }

        $this->cache = [
            'target_namespace' =>
            (string) $this->schema['targetNamespace'],

            'root' => $this->inspectElement(
                $root
            ),
        ];

        return $this->cache;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getrix(): ?array
    {
        return $this->inspect()['root'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function immobile(): ?array
    {
        $node = $this->findElement('Immobile');

        if ($node === null) {
            return null;
        }

        return $this->inspectElement($node);
    }

    /**
     * Find an XSD element anywhere in the schema.
     */
    private function findElement(
        string $name
    ): ?SimpleXMLElement {
        $nodes = $this->schema->xpath(
            '//xs:element[@name="' . $name . '"]'
        );

        if (
            $nodes === false ||
            !isset($nodes[0])
        ) {
            return null;
        }

        return $nodes[0];
    }

    /**
     * Public access to an XSD element for debugging/testing.
     */
    public function element(
        string $name
    ): ?SimpleXMLElement {
        return $this->findElement($name);
    }

    /**
     * @return array<string, mixed>
     */
    private function inspectElement(
        SimpleXMLElement $element
    ): array {
        $result = [
            'name' => (string) $element['name'],

            'type' => (
                isset($element['type'])
                ? (string) $element['type']
                : null
            ),

            /*
             * For anonymous simpleTypes, this contains the
             * restriction base, e.g. xs:decimal,
             * xs:unsignedByte, xs:string, etc.
             */
            'base_type' => null,

            'min_occurs' => $this->parseOccurs(
                $element['minOccurs'] ?? '1'
            ),

            'max_occurs' => $this->parseOccurs(
                $element['maxOccurs'] ?? '1'
            ),

            'nillable' => (
                (string) $element['nillable']
            ) === 'true',

            'documentation' => $this->documentation(
                $element
            ),

            'attributes' => [],

            'children' => [],

            'enumeration' => [],
        ];

        /*
         * Inline complexType.
         */
        $complexTypes = $element->xpath(
            './xs:complexType'
        );

        if (
            $complexTypes !== false &&
            isset($complexTypes[0])
        ) {
            $complexType = $complexTypes[0];

            $result['attributes'] =
                $this->inspectAttributes(
                    $complexType
                );

            $result['children'] =
                $this->inspectChildren(
                    $complexType
                );
        }

        /*
         * Inline simpleType.
         *
         * Getrix uses anonymous simpleTypes extensively.
         *
         * Example:
         *
         * <xs:element name="Prezzo">
         *     <xs:simpleType>
         *         <xs:restriction base="xs:decimal">
         *             ...
         *         </xs:restriction>
         *     </xs:simpleType>
         * </xs:element>
         */
        $simpleTypes = $element->xpath(
            './xs:simpleType'
        );

        if (
            $simpleTypes !== false &&
            isset($simpleTypes[0])
        ) {
            $simpleType = $simpleTypes[0];

            $result['enumeration'] =
                $this->inspectEnumeration(
                    $simpleType
                );

            $result['base_type'] =
                $this->inspectSimpleTypeBase(
                    $simpleType
                );
        }

        /*
         * Resolve named type.
         *
         * This handles definitions such as:
         *
         * type="string100"
         * type="xs:dateTime"
         * type="SomeCustomType"
         */
        if ($result['type'] !== null) {
            $typeName = $this->normalizeTypeName(
                $result['type']
            );

            /*
             * Resolve named complexType.
             */
            $complexType = $this->findComplexType(
                $typeName
            );

            if ($complexType !== null) {
                $result['attributes'] =
                    $this->inspectAttributes(
                        $complexType
                    );

                $result['children'] =
                    $this->inspectChildren(
                        $complexType
                    );
            }

            /*
             * Resolve named simpleType.
             */
            $simpleType = $this->findSimpleType(
                $typeName
            );

            if ($simpleType !== null) {
                $result['enumeration'] =
                    $this->inspectEnumeration(
                        $simpleType
                    );

                $result['base_type'] =
                    $this->inspectSimpleTypeBase(
                        $simpleType
                    );
            }
        }

        return $result;
    }

    /**
     * Extract the restriction base from an anonymous simpleType.
     *
     * Example:
     *
     * <xs:simpleType>
     *     <xs:restriction base="xs:decimal">
     *         ...
     *     </xs:restriction>
     * </xs:simpleType>
     *
     * Returns:
     *
     * xs:decimal
     */
    private function inspectSimpleTypeBase(
        SimpleXMLElement $simpleType
    ): ?string {
        $nodes = $simpleType->xpath(
            './xs:restriction/@base'
        );

        if (
            $nodes === false ||
            !isset($nodes[0])
        ) {
            return null;
        }

        $base = trim(
            (string) $nodes[0]
        );

        return $base !== ''
            ? $base
            : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function inspectChildren(
        SimpleXMLElement $complexType
    ): array {
        $result = [];

        /*
         * Getrix uses xs:all for Immobile,
         * but we support sequence too.
         */
        $nodes = $complexType->xpath(
            './xs:all/xs:element | ./xs:sequence/xs:element'
        );

        if ($nodes === false) {
            return $result;
        }

        foreach ($nodes as $element) {
            $name = (string) $element['name'];

            if ($name === '') {
                continue;
            }

            $result[$name] =
                $this->inspectElement(
                    $element
                );
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function inspectAttributes(
        SimpleXMLElement $complexType
    ): array {
        $result = [];

        $nodes = $complexType->xpath(
            './xs:attribute'
        );

        if ($nodes === false) {
            return $result;
        }

        foreach ($nodes as $attribute) {
            $name = (string) $attribute['name'];

            if ($name === '') {
                continue;
            }

            $result[$name] = [
                'type' => (
                    isset($attribute['type'])
                    ? (string) $attribute['type']
                    : null
                ),

                'use' => (
                    isset($attribute['use'])
                    ? (string) $attribute['use']
                    : 'optional'
                ),

                'default' => (
                    isset($attribute['default'])
                    ? (string) $attribute['default']
                    : null
                ),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function inspectEnumeration(
        SimpleXMLElement $simpleType
    ): array {
        $result = [];

        $nodes = $simpleType->xpath(
            './xs:restriction/xs:enumeration'
        );

        if ($nodes === false) {
            return $result;
        }

        foreach ($nodes as $enumeration) {
            $value = (string) $enumeration['value'];

            if ($value === '') {
                continue;
            }

            $result[$value] = [
                'value' => $value,
                'label' => $this->documentation(
                    $enumeration
                ) ?? $value,
            ];
        }

        return $result;
    }

    private function documentation(
        SimpleXMLElement $element
    ): ?string {
        $nodes = $element->xpath(
            './xs:annotation/xs:documentation'
        );

        if (
            $nodes === false ||
            !isset($nodes[0])
        ) {
            return null;
        }

        $value = trim(
            (string) $nodes[0]
        );

        return $value !== ''
            ? $value
            : null;
    }

    private function findComplexType(
        string $name
    ): ?SimpleXMLElement {
        $nodes = $this->schema->xpath(
            '//xs:complexType[@name="' . $name . '"]'
        );

        return (
            $nodes !== false &&
            isset($nodes[0])
        )
            ? $nodes[0]
            : null;
    }

    private function findSimpleType(
        string $name
    ): ?SimpleXMLElement {
        $nodes = $this->schema->xpath(
            '//xs:simpleType[@name="' . $name . '"]'
        );

        return (
            $nodes !== false &&
            isset($nodes[0])
        )
            ? $nodes[0]
            : null;
    }

    /**
     * Normalize a named type.
     *
     * Examples:
     *
     * xs:dateTime -> dateTime
     * xs:string   -> string
     * string100   -> string100
     */
    private function normalizeTypeName(
        string $type
    ): string {
        if (str_contains($type, ':')) {
            return explode(
                ':',
                $type,
                2
            )[1];
        }

        return $type;
    }

    private function parseOccurs(
        mixed $value
    ): int|string {
        $value = (string) $value;

        return $value === 'unbounded'
            ? 'unbounded'
            : (int) $value;
    }

    private function loadSchema(): SimpleXMLElement
    {
        if (!is_readable($this->schemaPath)) {
            throw new RuntimeException(
                sprintf(
                    'Getrix XSD not found: %s',
                    $this->schemaPath
                )
            );
        }

        libxml_use_internal_errors(true);

        $schema = simplexml_load_file(
            $this->schemaPath
        );

        if ($schema === false) {
            $errors = [];

            foreach (libxml_get_errors() as $error) {
                $errors[] = trim(
                    $error->message
                );
            }

            libxml_clear_errors();

            throw new RuntimeException(
                'Unable to parse Getrix XSD: ' .
                    implode('; ', $errors)
            );
        }

        return $schema;
    }
}
