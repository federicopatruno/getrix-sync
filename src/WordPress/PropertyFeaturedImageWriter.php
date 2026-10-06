<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Domain\Property;
use GetrixSync\Support\Config;

/**
 * WordPress's native featured image (post thumbnail) only works with
 * an attachment already in the media library -- it cannot point at
 * an external URL directly. This class downloads the Getrix cover
 * photo (the image with the lowest "posizione") into the media
 * library and sets it as the post's featured image.
 *
 * The full photo gallery (all images, as external Getrix URLs) is
 * handled separately by PropertyAcfWriter::mapImages() and is not
 * affected by this class.
 */
final class PropertyFeaturedImageWriter
{
    public function write(int $postId, Property $property): void
    {
        if (!(bool) Config::get('sync.download_images', true)) {
            return;
        }

        $cover = $this->coverImage($property);

        if ($cover === null || ($cover['url'] ?? '') === '') {
            return;
        }

        $sourceId = (string) (
            $cover['id'] ?? $cover['url']
        );

        $currentSource = get_post_meta(
            $postId,
            PropertyMeta::FEATURED_IMAGE_SOURCE,
            true
        );

        /*
         * Nothing to do if the same Getrix image is already set as
         * the featured image (the common case on every sync after
         * the first one) -- avoids re-downloading the same photo
         * every day.
         */
        if (
            $currentSource === $sourceId
            && has_post_thumbnail($postId)
        ) {
            return;
        }

        $attachmentId = $this->sideload(
            (string) $cover['url'],
            $postId,
            $property
        );

        if ($attachmentId === null) {
            return;
        }

        set_post_thumbnail($postId, $attachmentId);

        update_post_meta(
            $postId,
            PropertyMeta::FEATURED_IMAGE_SOURCE,
            $sourceId
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function coverImage(Property $property): ?array
    {
        if ($property->images === []) {
            return null;
        }

        $images = $property->images;

        usort(
            $images,
            static function (array $a, array $b): int {
                $posA = $a['posizione'] ?? PHP_INT_MAX;
                $posB = $b['posizione'] ?? PHP_INT_MAX;

                return $posA <=> $posB;
            }
        );

        return $images[0];
    }

    private function sideload(
        string $url,
        int $postId,
        Property $property
    ): ?int {
        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH
                . 'wp-admin/includes/media.php';
            require_once ABSPATH
                . 'wp-admin/includes/file.php';
            require_once ABSPATH
                . 'wp-admin/includes/image.php';
        }

        $description = $property->title();

        $result = media_sideload_image(
            $url,
            $postId,
            $description !== '' ? $description : null,
            'id'
        );

        if (is_wp_error($result)) {
            $this->log(sprintf(
                'Featured image download failed for getrix_id %s '
                    . '(post #%d): %s',
                $property->getrixId,
                $postId,
                $result->get_error_message()
            ));

            return null;
        }

        return (int) $result;
    }

    private function log(string $message): void
    {
        if (!function_exists('error_log')) {
            return;
        }

        error_log('[GetrixSync] ' . $message);
    }
}
