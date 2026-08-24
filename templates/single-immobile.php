<?php

/**
 * Fallback template for a single "Immobile".
 *
 * Loaded by GetrixSync\WordPress\TemplateLoader via template_include
 * whenever the active theme does not provide its own
 * single-immobile.php. Because it calls get_header()/get_footer(),
 * it still inherits the active theme's header, footer, menu, colors
 * and fonts — only the content area is controlled here.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

while (have_posts()):
    the_post();

    $id                = get_the_ID();
    $prezzo            = get_field('prezzo', $id);
    $trattativaRis     = get_field('trattativa_riservata', $id);
    $contrattoLabel    = get_field('contratto_label', $id) ?: get_field('contratto', $id);
    $categoriaLabel    = get_field('categoria_label', $id) ?: get_field('categoria', $id);
    $tipologia         = get_field('tipologia', $id);
    $mq                = get_field('mq_superficie', $id);
    $nrLocali          = get_field('nr_locali', $id);
    $nrServiziIgiene   = get_field('nr_servizi_igiene', $id);
    $pianiEdificio     = get_field('piani_edificio', $id);
    $classeEnergetica  = get_field('classe_energetica', $id);

    $indirizzo         = get_field('indirizzo', $id);
    $civico            = get_field('civico', $id);
    $pubblicaIndirizzo = get_field('pubblica_indirizzo', $id);
    $zona              = get_field('zona', $id);
    $comune            = get_field('comune', $id);
    $cap               = get_field('cap', $id);
    $lat               = get_field('latitudine', $id);
    $lng               = get_field('longitudine', $id);
    $pubblicaMappa     = get_field('pubblica_mappa', $id);

    $descrizioneTitolo = get_field('descrizione_titolo', $id);
    $descrizioneTesto  = get_field('descrizione_testo', $id);

    $ascensore         = get_field('ascensore', $id);
    $ariaCondizionata  = get_field('aria_condizionata', $id);
    $cablaggioRete     = get_field('cablaggio_rete', $id);
    $portineria        = get_field('portineria', $id);
    $nuovaCostruzione  = get_field('nuova_costruzione', $id);
    $statoImmobile     = get_field('stato_immobile_label', $id) ?: get_field('stato_immobile', $id);
    $riscaldamento     = get_field('riscaldamento_label', $id) ?: get_field('riscaldamento', $id);

    $immagini          = get_field('immagini_getrix', $id) ?: [];
    ?>

    <main class="getrix-immobile getrix-immobile--single">
        <article <?php post_class(); ?>>

            <header class="getrix-immobile__header">
                <h1 class="getrix-immobile__title">
                    <?php echo esc_html($descrizioneTitolo ?: get_the_title()); ?>
                </h1>

                <p class="getrix-immobile__breadcrumb">
                    <?php
                    echo esc_html(trim(implode(', ', array_filter([
                        $pubblicaIndirizzo ? trim($indirizzo . ' ' . $civico) : null,
                        $zona,
                        $comune,
                    ]))));
                    ?>
                </p>

                <p class="getrix-immobile__price">
                    <?php if ($trattativaRis): ?>
                        Prezzo su richiesta
                    <?php elseif ($prezzo): ?>
                        &euro; <?php echo esc_html(number_format_i18n((float) $prezzo, 0)); ?>
                    <?php endif; ?>
                </p>
            </header>

            <?php if (has_post_thumbnail() || !empty($immagini)): ?>
                <div class="getrix-immobile__gallery">
                    <?php if (has_post_thumbnail()): ?>
                        <figure class="getrix-immobile__gallery-item getrix-immobile__gallery-item--cover">
                            <?php the_post_thumbnail('large'); ?>
                        </figure>
                    <?php endif; ?>

                    <?php foreach ($immagini as $immagine): ?>
                        <?php if (empty($immagine['url'])) continue; ?>
                        <figure class="getrix-immobile__gallery-item">
                            <img
                                src="<?php echo esc_url($immagine['url']); ?>"
                                alt="<?php echo esc_attr($immagine['titolo'] ?: get_the_title()); ?>"
                                loading="lazy"
                            >
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="getrix-immobile__summary">
                <ul class="getrix-immobile__facts">
                    <?php if ($categoriaLabel): ?>
                        <li><strong>Categoria:</strong> <?php echo esc_html($categoriaLabel); ?></li>
                    <?php endif; ?>
                    <?php if ($tipologia): ?>
                        <li><strong>Tipologia:</strong> <?php echo esc_html($tipologia); ?></li>
                    <?php endif; ?>
                    <?php if ($contrattoLabel): ?>
                        <li><strong>Contratto:</strong> <?php echo esc_html($contrattoLabel); ?></li>
                    <?php endif; ?>
                    <?php if ($mq): ?>
                        <li><strong>Superficie:</strong> <?php echo esc_html($mq); ?> mq</li>
                    <?php endif; ?>
                    <?php if ($nrLocali): ?>
                        <li><strong>Locali:</strong> <?php echo esc_html($nrLocali); ?></li>
                    <?php endif; ?>
                    <?php if ($nrServiziIgiene): ?>
                        <li><strong>Bagni:</strong> <?php echo esc_html($nrServiziIgiene); ?></li>
                    <?php endif; ?>
                    <?php if ($pianiEdificio): ?>
                        <li><strong>Piani edificio:</strong> <?php echo esc_html($pianiEdificio); ?></li>
                    <?php endif; ?>
                    <?php if ($statoImmobile): ?>
                        <li><strong>Stato:</strong> <?php echo esc_html($statoImmobile); ?></li>
                    <?php endif; ?>
                    <?php if ($classeEnergetica): ?>
                        <li><strong>Classe energetica:</strong> <?php echo esc_html($classeEnergetica); ?></li>
                    <?php endif; ?>
                    <?php if ($riscaldamento): ?>
                        <li><strong>Riscaldamento:</strong> <?php echo esc_html($riscaldamento); ?></li>
                    <?php endif; ?>
                </ul>

                <ul class="getrix-immobile__amenities">
                    <?php if ($ascensore): ?><li>Ascensore</li><?php endif; ?>
                    <?php if ($ariaCondizionata): ?><li>Aria condizionata</li><?php endif; ?>
                    <?php if ($cablaggioRete): ?><li>Cablaggio rete</li><?php endif; ?>
                    <?php if ($portineria): ?><li>Portineria</li><?php endif; ?>
                    <?php if ($nuovaCostruzione): ?><li>Nuova costruzione</li><?php endif; ?>
                </ul>
            </section>

            <?php if ($descrizioneTesto): ?>
                <section class="getrix-immobile__description">
                    <h2>Descrizione</h2>
                    <?php echo wp_kses_post($descrizioneTesto); ?>
                </section>
            <?php endif; ?>

            <?php if ($pubblicaMappa && $lat && $lng): ?>
                <section class="getrix-immobile__map">
                    <h2>Posizione</h2>
                    <iframe
                        title="Mappa immobile"
                        width="100%"
                        height="400"
                        style="border:0"
                        loading="lazy"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=<?php
                            echo esc_attr(($lng - 0.01) . ',' . ($lat - 0.01) . ',' . ($lng + 0.01) . ',' . ($lat + 0.01));
                        ?>&marker=<?php echo esc_attr($lat . ',' . $lng); ?>&layer=mapnik"
                    ></iframe>
                </section>
            <?php endif; ?>

        </article>
    </main>

<?php
endwhile;

get_footer();
