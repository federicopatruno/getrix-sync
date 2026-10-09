<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Domain\Property;
use GetrixSync\Support\Config;

/**
 * Fills the taxonomies of a synced property:
 *
 * - category (hierarchical): parent = "categoria_label"
 *   (e.g. "Immobili Commerciali"), child = "tipologia"
 *   (e.g. "Ufficio"). The post gets both terms.
 * - tags (flat): "contratto_label", "tipologia_uso_label" and
 *   "tipo_costruzione_label" (e.g. "Vendita", "commerciale",
 *   "signorile").
 *
 * Terms are created on demand. On every sync only the terms this
 * class assigned previously are replaced (tracked in post meta), so
 * a listing that changes contract or type loses the stale term,
 * while categories/tags an editor added by hand are left alone.
 */
final class PropertyTermsWriter
{
    /**
     * Label fields used as tags, looked up in every data bucket of
     * the property (they live in different blocks of the feed).
     */
    private const TAG_FIELDS = [
        'contratto_label',
        'tipologia_uso_label',
        'tipo_costruzione_label',
    ];

    public function write(int $postId, Property $property): void
    {
        $this->writeCategories($postId, $property);
        $this->writeTags($postId, $property);
    }

    private function writeCategories(
        int $postId,
        Property $property
    ): void {
        $taxonomy = (string) Config::get(
            'taxonomies.category',
            'category'
        );

        $parentName = $this->clean(
            $property->data['categoria_label'] ?? null
        );

        if ($parentName === '' || !taxonomy_exists($taxonomy)) {
            return;
        }

        $parentId = $this->ensureTerm($parentName, $taxonomy, 0);

        if ($parentId === null) {
            return;
        }

        $termIds = [$parentId];

        $childName = $this->clean($property->data['tipologia'] ?? null);

        if ($childName !== '') {
            $childId = $this->ensureTerm(
                $childName,
                $taxonomy,
                $parentId
            );

            if ($childId !== null) {
                $termIds[] = $childId;
            }
        }

        $this->syncTerms(
            $postId,
            $taxonomy,
            $termIds,
            PropertyMeta::SYNCED_CATEGORY_IDS
        );
    }

    private function writeTags(int $postId, Property $property): void
    {
        $taxonomy = (string) Config::get(
            'taxonomies.tag',
            'post_tag'
        );

        if (!taxonomy_exists($taxonomy)) {
            return;
        }

        $buckets = [
            $property->data,
            $property->commercial,
            $property->residential,
            $property->land,
        ];

        $termIds = [];

        foreach (self::TAG_FIELDS as $field) {
            foreach ($buckets as $bucket) {
                $name = $this->clean($bucket[$field] ?? null);

                if ($name === '') {
                    continue;
                }

                $termId = $this->ensureTerm($name, $taxonomy, 0);

                if ($termId !== null) {
                    $termIds[] = $termId;
                }

                break;
            }
        }

        $this->syncTerms(
            $postId,
            $taxonomy,
            $termIds,
            PropertyMeta::SYNCED_TAG_IDS
        );
    }

    /**
     * Find a term by name (under the given parent) or create it.
     */
    private function ensureTerm(
        string $name,
        string $taxonomy,
        int $parentId
    ): ?int {
        $existing = term_exists($name, $taxonomy, $parentId);

        if (is_array($existing)) {
            return (int) $existing['term_id'];
        }

        if (is_int($existing) && $existing > 0) {
            return $existing;
        }

        $args = [];

        if ($parentId > 0) {
            $args['parent'] = $parentId;
        }

        $result = wp_insert_term($name, $taxonomy, $args);

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

    private function clean(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
