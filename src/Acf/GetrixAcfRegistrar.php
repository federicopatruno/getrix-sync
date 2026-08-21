<?php

declare(strict_types=1);

namespace GetrixSync\Acf;

use RuntimeException;

final class GetrixAcfRegistrar
{
    /**
     * Register the Getrix ACF field group.
     *
     * @param array<string, mixed> $fieldGroup
     */
    public function register(array $fieldGroup): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            throw new RuntimeException(
                'ACF Pro is required to register the Getrix field group.'
            );
        }

        acf_add_local_field_group($fieldGroup);
    }
}
