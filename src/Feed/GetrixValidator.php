<?php

declare(strict_types=1);

namespace GetrixSync\Feed;

use GetrixSync\Support\Config;
use RuntimeException;

final class GetrixValidator
{
    public function validate(string $xml): void
    {
        if (!class_exists(\DOMDocument::class)) {
            throw new RuntimeException(
                'PHP DOM extension is required to validate the Getrix feed.'
            );
        }

        $xsdUrl = (string) Config::get('feed.xsd_url', '');

        if ($xsdUrl === '') {
            throw new RuntimeException(
                'Getrix XSD URL is not configured.'
            );
        }

        $document = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);

        try {
            if (!$document->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException(
                    $this->formatErrors(
                        'Unable to load Getrix XML'
                    )
                );
            }

            if (!$document->schemaValidate($xsdUrl)) {
                throw new RuntimeException(
                    $this->formatErrors(
                        'Getrix XML failed XSD validation'
                    )
                );
            }
        } finally {
            libxml_use_internal_errors($previous);
            libxml_clear_errors();
        }
    }

    private function formatErrors(string $prefix): string
    {
        $errors = libxml_get_errors();

        if ($errors === []) {
            return $prefix . '.';
        }

        $messages = array_map(
            static fn(\LibXMLError $error): string => trim(
                $error->message
            ),
            $errors
        );

        return $prefix . ': ' . implode('; ', $messages);
    }
}
