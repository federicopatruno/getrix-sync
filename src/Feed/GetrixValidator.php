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

        $xsdSource = $this->resolveXsdSource();

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

            if (!$document->schemaValidate($xsdSource)) {
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

    /**
     * Prefer the XSD bundled with the plugin (no network dependency
     * on every sync run); fall back to the remote URL if the local
     * copy is missing or disabled via config.
     */
    private function resolveXsdSource(): string
    {
        $localPath = Config::get('feed.xsd_local_path');

        if (
            is_string($localPath)
            && $localPath !== ''
            && is_readable($localPath)
        ) {
            return $localPath;
        }

        $xsdUrl = (string) Config::get('feed.xsd_url', '');

        if ($xsdUrl === '') {
            throw new RuntimeException(
                'Neither a local nor a remote Getrix XSD is configured.'
            );
        }

        return $xsdUrl;
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
