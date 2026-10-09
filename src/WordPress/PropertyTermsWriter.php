<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Domain\Property;
use GetrixSync\Support\Config;

/**
 * Fills the custom taxonomies of a synced property (Categoria
 * Immobile, Tipologia, Contratto, Tipologia d'uso, Tipo
 * costruzione), each from the feed field configured under
 * 'taxonomies.terms' in config/plugin.php.
 *
 * Terms are created on demand. On every sync only the terms this
 * class assigned previously are replaced (tracked in post meta), so
 * a listing that changes contract or type loses the stale term,
 * while terms an editor added by hand are left alone.
 */
final class PropertyTermsWriter
{
    public function write(int $postId, Property $property): void
    {
        $definitions = Config::get('taxonomies.terms', []);

        if (is_array($definitions)) {
            foreach ($definitions as $definition) {
                $this->writeCustomTaxonomy(
                    $postId,
                    $property,
                    (array) $definition
                );
            }
        }

        $this->detachLegacyTerms($postId);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function writeCustomTaxonomy(
        int $postId,
        Property $property,
        array $definition
    ): void {
        $taxonomy = (string) ($definition['taxonomy'] ?? '');
        $field = (string) ($definition['field'] ?? '');

        if (
            $taxonomy === ''
            || $field === ''
            || !taxonomy_exists($taxonomy)
        ) {
            return;
        }

        $name = $this->fieldValue($property, $field);

        $termIds = [];

        if ($name !== '') {
            $termId = $this->ensureTerm($name, $taxonomy);

            if ($termId !== null) {
                $termIds[] = $termId;
            }
        }

        $this->syncTerms(
            $postId,
            $taxonomy,
            $termIds,
            PropertyMeta::SYNCED_TERMS_PREFIX . $taxonomy
        );
    }

    /**
     * The value may live in the common data or in any of the
     * Commerciale / Residenziale / Terreno blocks.
     */
    private function fieldValue(Property $property, string $field): string
    {
        foreach ([
            $property->data,
            $property->commercial,
            $property->residential,
            $property->land,
        ] as $bucket) {
            $value = $this->clean($bucket[$field] ?? null);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Find a top-level term by name or create it.
     */
    private function ensureTerm(string $name, string $taxonomy): ?int
    {
        $existing = term_exists($name, $taxonomy, 0);

        if (is_array($existing)) {
            return (int) $existing['term_id'];
        }

        if (is_int($existing) && $existing > 0) {
            return $existing;
        }

        $result = wp_insert_term($name, $taxonomy);

        if (is_wp_error($result)) {
            $existingId = $result->get_error_data('term_exists');

            if ($existingId) {
                return (int) $existingId;
            }

            error_log(sprintf(
                '[GetrixSync] Unable to create %s term "%s": %s',
                $taxonomy,
                $name,
                $result->get_error_message()
            ));

            return null;
        }

        return (int) $result['term_id'];
    }

    /**
     * Replace the terms previously assigned by the sync with the new
     * ones, keeping any term that was added manually.
     *
     * @param array<int, int> $newIds
     */
    private function syncTerms(
        int $postId,
        string $taxonomy,
        array $newIds,
        string $metaKey
    ): void {
        $previous = get_post_meta($postId, $metaKey, true);
        $previous = is_array($previous)
            ? array_map('intval', $previous)
            : [];

        $current = wp_get_object_terms(
            $postId,
            $taxonomy,
            ['fields' => 'ids']
        );

        $current = is_wp_error($current)
            ? []
            : array_map('intval', $current);

        $manual = array_diff($current, $previous);

        $final = array_values(array_unique(array_merge(
            $manual,
            array_map('intval', $newIds)
        )));

        wp_set_object_terms($postId, $final, $taxonomy, false);

        update_post_meta(
            $postId,
            $metaKey,
            array_map('intval', $newIds)
        );
    }

    /**
     * Earlier versions of the plugin assigned built-in categories
     * (including sub-categories) and tags. Detach the ones they
     * added (tracked in meta) from the post, once, and drop the
     * bookkeeping meta. The terms themselves are not deleted because
     * they may be used elsewhere on the site.
     */
    private function detachLegacyTerms(int $postId): void
    {
        $legacy = [
            PropertyMeta::SYNCED_CATEGORY_IDS => 'category',
            PropertyMeta::SYNCED_TAG_IDS => 'post_tag',
        ];

        foreach ($legacy as $metaKey => $taxonomy) {
            $ids = get_post_meta($postId, $metaKey, true);

            if (!is_array($ids)) {
                continue;
            }

            if ($ids !== [] && taxonomy_exists($taxonomy)) {
                wp_remove_object_terms(
                    $postId,
                    array_map('intval', $ids),
                    $taxonomy
                );
            }

            delete_post_meta($postId, $metaKey);
        }
    }

    private function clean(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
