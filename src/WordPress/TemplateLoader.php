<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;

/**
 * Makes the "Immobile" post type render correctly on ANY active theme.
 *
 * WordPress normally lets the active theme decide how a custom post type
 * is displayed (single-{post_type}.php, archive-{post_type}.php, or a
 * generic fallback that often just prints the raw content). Since themes
 * change but the data shouldn't break, this class:
 *
 *  1. Lets the active theme provide its own single-immobile.php /
 *     archive-immobile.php if the theme (or a child theme) defines one —
 *     so designers can still fully customize the markup.
 *  2. Otherwise falls back to the templates shipped inside this plugin,
 *     so the listings always render something correct out of the box.
 */
final class TemplateLoader implements ServiceProvider
{
    public function register(Container $container): void
    {
        // No container bindings required.
    }

    public function boot(Container $container): void
    {
        add_filter('template_include', [$this, 'loadTemplate']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Loads the base stylesheet only on the single/archive Immobile
     * views, so it never affects the rest of the site. Themes can
     * still override every rule (all selectors are scoped under
     * .getrix-immobile / .getrix-immobile-card, which is easy to
     * out-specify) or dequeue this handle entirely from
     * functions.php: wp_dequeue_style('getrix-immobile').
     */
    public function enqueueAssets(): void
    {
        $postType = (string) Config::get('post_type', 'immobile');

        if (!is_singular($postType) && !is_post_type_archive($postType)) {
            return;
        }

        $file = dirname(__DIR__, 2) . '/assets/getrix-immobile.css';

        if (!is_file($file)) {
            return;
        }

        wp_enqueue_style(
            'getrix-immobile',
            plugins_url('assets/getrix-immobile.css', dirname(__DIR__, 2) . '/getrix-sync.php'),
            [],
            (string) filemtime($file)
        );
    }

    public function loadTemplate(string $template): string
    {
        $postType = (string) Config::get('post_type', 'immobile');

        if (is_singular($postType)) {
            return $this->resolve('single-' . $postType . '.php', 'single-immobile.php', $template);
        }

        if (is_post_type_archive($postType)) {
            return $this->resolve('archive-' . $postType . '.php', 'archive-immobile.php', $template);
        }

        return $template;
    }

    /**
     * Bonus: the active theme (or child theme) always wins if it ships
     * its own template file with the expected name. Only when the theme
     * has nothing do we fall back to the plugin's bundled template.
     */
    private function resolve(string $themeFileName, string $pluginFileName, string $default): string
    {
        $themeTemplate = locate_template([$themeFileName]);

        if ($themeTemplate !== '') {
            return $themeTemplate;
        }

        $pluginTemplate = dirname(__DIR__, 2) . '/templates/' . $pluginFileName;

        if (is_file($pluginTemplate)) {
            return $pluginTemplate;
        }

        return $default;
    }
}
