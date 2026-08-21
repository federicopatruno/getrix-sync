<?php

declare(strict_types=1);

namespace GetrixSync\Domain;

use RuntimeException;

final class GetrixSchema
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $enumerations = [];

    public function __construct(
        private readonly string $schemaPath
    ) {
        $this->load();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function enumerations(): array
    {
        return $this->enumerations;
    }

    /**
     * @return array<string, string>
     */
    public function values(string $field): array
    {
        return $this->enumerations[$field] ?? [];
    }

    public function resolve(
        string $field,
        string|int|null $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return $this->enumerations[$field][$value]
            ?? null;
    }

    private function load(): void
    {
        if (!is_readable($this->schemaPath)) {
            throw new RuntimeException(
                sprintf(
                    'Getrix XSD not found: %s',
                    $this->schemaPath
                )
            );
        }

        $xml = simplexml_load_file(
            $this->schemaPath
        );

        if ($xml === false) {
            throw new RuntimeException(
                'Unable to parse Getrix XSD.'
            );
        }

        $namespaces = $xml->getDocNamespaces(true);

        $xs = $xml->children(
            $namespaces['xs'] ?? 'http://www.w3.org/2001/XMLSchema'
        );

        $this->extractEnumerations(
            $xs
        );
    }

    private function extractEnumerations(
        \SimpleXMLElement $schema
    ): void {
        foreach ($schema->element as $element) {
            $name = (string) $element['name'];

            if ($name === '') {
                continue;
            }

            $this->extractFromElement(
                $name,
                $element
            );
        }

        foreach ($schema->complexType as $complexType) {
            $this->extractNestedElements(
                $complexType
            );
        }
    }

    private function extractNestedElements(
        \SimpleXMLElement $parent
    ): void {
        foreach (
            $parent->xpath(
                './/*[local-name()="element"]'
            ) ?: []
            as $element
        ) {
            $name = (string) $element['name'];

            if ($name === '') {
                continue;
            }

            $this->extractFromElement(
                $name,
                $element
            );
        }
    }

    private function extractFromElement(
        string $name,
        \SimpleXMLElement $element
    ): void {
        $enumerations = [];

        foreach (
            $element->xpath(
                './/*[local-name()="enumeration"]'
            ) ?: []
            as $enumeration
        ) {
            $value = (string) $enumeration['value'];

            $documentation = $enumeration->xpath(
                './/*[local-name()="documentation"]'
            );

            $label = '';

            if (
                isset($documentation[0])
            ) {
                $label = trim(
                    (string) $documentation[0]
                );
            }

            if ($value !== '') {
                $enumerations[$value] = $label;
            }
        }

        if ($enumerations !== []) {
            $this->enumerations[$name] =
                $enumerations;
        }
    }
}
