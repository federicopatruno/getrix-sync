<?php

/**
 * Fallback template for the "Immobile" archive (listing grid).
 *
 * Loaded by GetrixSync\WordPress\TemplateLoader via template_include
 * whenever the active theme does not provide its own
 * archive-immobile.php.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
?>

<main class="getrix-immobile-archive">
    <header class="getrix-immobile-archive__header">
        <h1><?php post_type_archive_title(); ?></h1>
    </header>

    <?php if (have_posts()): ?>

        <div class="getrix-immobile-archive__grid">
            <?php while (have_posts()): the_post();
                $id             = get_the_ID();
                $prezzo         = get_field('prezzo', $id);
                $trattativaRis  = get_field('trattativa_riservata', $id);
                $mq             = get_field('mq_superficie', $id);
                $nrLocali       = get_field('nr_locali', $id);
                $comune         = get_field('comune', $id);
                $zona           = get_field('zona', $id);
                $contrattoLabel = get_field('contratto_label', $id) ?: get_field('contratto', $id);
                $categoriaLabel = get_field('categoria_label', $id) ?: get_field('categoria', $id);
                ?>

                <a href="<?php the_permalink(); ?>" class="getrix-immobile-card">
                    <div class="getrix-immobile-card__image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium_large'); ?>
                        <?php endif; ?>
                        <?php if ($contrattoLabel): ?>
                            <span class="getrix-immobile-card__badge"><?php echo esc_html($contrattoLabel); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="getrix-immobile-card__body">
                        <h2 class="getrix-immobile-card__title"><?php the_title(); ?></h2>

                        <p class="getrix-immobile-card__location">
                            <?php echo esc_html(trim(implode(', ', array_filter([$zona, $comune])))); ?>
                        </p>

                        <ul class="getrix-immobile-card__facts">
                            <?php if ($categoriaLabel): ?><li><?php echo esc_html($categoriaLabel); ?></li><?php endif; ?>
                            <?php if ($mq): ?><li><?php echo esc_html($mq); ?> mq</li><?php endif; ?>
                            <?php if ($nrLocali): ?><li><?php echo esc_html($nrLocali); ?> locali</li><?php endif; ?>
                        </ul>

                        <p class="getrix-immobile-card__price">
                            <?php if ($trattativaRis): ?>
                                Prezzo su richiesta
                            <?php elseif ($prezzo): ?>
                                &euro; <?php echo esc_html(number_format_i18n((float) $prezzo, 0)); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>

            <?php endwhile; ?>
        </div>

        <nav class="getrix-immobile-archive__pagination">
            <?php the_posts_pagination(); ?>
        </nav>

    <?php else: ?>

        <p><?php esc_html_e('Nessun immobile disponibile al momento.', 'getrix-sync'); ?></p>

    <?php endif; ?>
</main>

<?php
get_footer();
