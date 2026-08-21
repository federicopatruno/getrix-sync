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
    }

    /**
     * Return the complete schema.
     *
     * @return array<string, mixed>
     */
    public function inspect(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $xs = $this->schema->children(
            self::XS_NAMESPACE
        );

        return $this->cache = [
            'target_namespace' => (string) $this->schema['targetNamespace'],
            'root' => $this->inspectRoot($xs),
        ];
    }

    /**
     * Return the Getrix root definition.
     *
     * @return array<string, mixed>|null
     */
    public function getrix(): ?array
    {
        return $this->inspect()['root'];
    }

    /**
     * Return the Immobile definition.
     *
     * @return array<string, mixed>|null
     */
    public function immobile(): ?array
    {
        $getrix = $this->getrix();

        if ($getrix === null) {
            return null;
        }

        return $getrix['children']['Immobile']
            ?? null;
    }

    /**
     * @param SimpleXMLElement $elements
     *
     * @return array<string, mixed>|null
     */
    private function inspectRoot(
        SimpleXMLElement $xs
    ): ?array {
        foreach ($xs->element as $element) {
            if (
                (string) $element['name']
                !== 'Getrix'
            ) {
                continue;
            }

            return [
                'name' => 'Getrix',
                'children' => $this->inspectSequence(
                    $element
                ),
            ];
        }

        return null;
    }

    /**
     * Inspect the complex type / sequence of an element.
     *
     * @return array<string, mixed>
     */
    private function inspectSequence(
        SimpleXMLElement $element
    ): array {
        $result = [];

        $complexType =
            $element->children(self::XS_NAMESPACE)
            ->complexType;

        if (!isset($complexType[0])) {
            return $result;
        }

        $children = $complexType[0]
            ->children(self::XS_NAMESPACE);

        /*
         * The Getrix XSD uses both xs:sequence
         * and xs:all. Support both.
         */
        foreach (
            [
                ...iterator_to_array($children->sequence),
                ...iterator_to_array($children->all),
            ] as $container
        ) {
            foreach (
                $container->children(self::XS_NAMESPACE)->element
                as $child
            ) {
                $name = (string) $child['name'];

                if ($name === '') {
                    continue;
                }

                $result[$name] =
                    $this->inspectElement($child);
            }
        }

        return $result;
    }

    /**
     * Inspect an individual XSD element.
     *
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
            'min_occurs' => $this->parseOccurs(
                $element['minOccurs'] ?? '1'
            ),
            'max_occurs' => $this->parseOccurs(
                $element['maxOccurs'] ?? '1'
            ),
            'attributes' => [],
            'children' => [],
            'enumeration' => [],
            'documentation' => null,
        ];

        /*
         * Documentation directly attached to the element.
         */
        $annotations =
            $element->children(self::XS_NAMESPACE)
            ->annotation;

        if (isset($annotations[0])) {
            $documentation =
                $annotations[0]
                ->children(self::XS_NAMESPACE)
                ->documentation;

            if (isset($documentation[0])) {
                $result['documentation'] =
                    trim((string) $documentation[0]);
            }
        }

        $complexTypes =
            $element->children(self::XS_NAMESPACE)
            ->complexType;

        if (isset($complexTypes[0])) {
            $complexType = $complexTypes[0];

            $result['attributes'] =
                $this->inspectAttributes(
                    $complexType
                );

            $children =
                $this->inspectComplexChildren(
                    $complexType
                );

            if ($children !== []) {
                $result['children'] = $children;
            }
        }

        /*
         * Inline simpleType / enumeration.
         */
        $simpleTypes =
            $element->children(self::XS_NAMESPACE)
            ->simpleType;

        if (isset($simpleTypes[0])) {
            $result['enumeration'] =
                $this->inspectEnumeration(
                    $simpleTypes[0]
                );
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function inspectComplexChildren(
        SimpleXMLElement $complexType
    ): array {
        $result = [];

        $containers =
            $complexType->children(self::XS_NAMESPACE);

        foreach (
            [
                ...iterator_to_array($containers->sequence),
                ...iterator_to_array($containers->all),
            ] as $container
        ) {
            foreach (
                $container->children(self::XS_NAMESPACE)->element
                as $element
            ) {
                $name = (string) $element['name'];

                if ($name === '') {
                    continue;
                }

                $result[$name] =
                    $this->inspectElement($element);
            }
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

        $restriction =
            $simpleType
            ->children(self::XS_NAMESPACE)
            ->restriction;

        if (!isset($restriction[0])) {
            return $result;
        }

        foreach (
            $restriction[0]
                ->children(self::XS_NAMESPACE)
                ->enumeration
            as $enumeration
        ) {
            $value = (string) $enumeration['value'];

            $label = '';

            $annotation =
                $enumeration
                ->children(self::XS_NAMESPACE)
                ->annotation;

            if (isset($annotation[0])) {
                $documentation =
                    $annotation[0]
                    ->children(self::XS_NAMESPACE)
                    ->documentation;

                if (isset($documentation[0])) {
                    $label = trim(
                        (string) $documentation[0]
                    );
                }
            }

            $result[$value] = [
                'value' => $value,
                'label' => $label,
            ];
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

        foreach (
            $complexType
                ->children(self::XS_NAMESPACE)
                ->attribute
            as $attribute
        ) {
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
