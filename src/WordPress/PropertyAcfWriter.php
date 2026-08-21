<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Domain\Property;
use RuntimeException;

final class PropertyAcfWriter
{
    public function write(
        int $postId,
        Property $property
    ): void {
        if (!function_exists('update_field')) {
            throw new RuntimeException(
                'ACF Pro is required to synchronize property fields.'
            );
        }

        $data = $property->data;

        $this->update(
            'getrix_id',
            $property->getrixId,
            $postId
        );

        foreach ($data as $field => $value) {
            $this->update(
                $field,
                $value,
                $postId
            );
        }

        $this->update(
            'descrizione_titolo',
            $property->descriptions[0]['titolo'] ?? null,
            $postId
        );

        $this->update(
            'descrizione_testo',
            $property->description(),
            $postId
        );

        $this->update(
            'descrizione_breve',
            $property->shortDescription(),
            $postId
        );

        $this->update(
            'immagini_getrix',
            $this->mapImages($property->images),
            $postId
        );

        foreach ($property->commercial as $field => $value) {
            $this->update(
                $field,
                $value,
                $postId
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $images
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapImages(array $images): array
    {
        return array_map(
            static function (array $image): array {
                return [
                    'id_immagine' => $image['id'] ?? null,
                    'tipo' => $image['tipo'] ?? null,
                    'url' => $image['url'] ?? null,
                    'data_modifica' =>
                    $image['data_modifica'] ?? null,
                    'titolo' => $image['titolo'] ?? null,
                    'posizione' => $image['posizione'] ?? null,
                ];
            },
            $images
        );
    }

    private function update(
        string $field,
        mixed $value,
        int $postId
    ): void {
        if ($value === null) {
            return;
        }

        update_field(
            $field,
            $value,
            $postId
        );
    }
}
