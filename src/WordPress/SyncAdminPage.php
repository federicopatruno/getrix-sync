<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Sync\SyncManager;
use RuntimeException;

final class SyncAdminPage implements ServiceProvider
{
    public function register(Container $container): void
    {
        // No additional services.
    }

    public function boot(Container $container): void
    {
        add_action(
            'admin_menu',
            [$this, 'registerMenu']
        );

        add_action(
            'admin_post_getrix_sync_one',
            [$this, 'syncOne']
        );

        add_action(
            'admin_post_getrix_sync_all',
            [$this, 'syncAll']
        );
    }

    public function registerMenu(): void
    {
        add_management_page(
            'Getrix Sync',
            'Getrix Sync',
            'manage_options',
            'getrix-sync',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Non hai i permessi necessari.'
            );
        }

?>
        <div class="wrap">
            <h1>Getrix Sync</h1>

            <p>
                Test di sincronizzazione di un singolo
                immobile dal feed Getrix.
            </p>

            <?php if (
                isset($_GET['getrix_sync'])
                && $_GET['getrix_sync'] === 'success'
            ) : ?>

                <div class="notice notice-success is-dismissible">
                    <p>
                        Immobile sincronizzato correttamente.
                        Post ID:
                        <strong>
                            <?php
                            echo esc_html(
                                (string) (
                                    $_GET['post_id'] ?? ''
                                )
                            );
                            ?>
                        </strong>
                    </p>
                </div>

            <?php endif; ?>

            <?php if (
                isset($_GET['getrix_sync_all'])
                && $_GET['getrix_sync_all'] === 'success'
            ) : ?>

                <div class="notice notice-success is-dismissible">
                    <p>
                        Sincronizzazione completa eseguita.
                        Totale nel feed:
                        <strong><?php echo esc_html((string) ($_GET['total'] ?? '0')); ?></strong>,
                        creati:
                        <strong><?php echo esc_html((string) ($_GET['created'] ?? '0')); ?></strong>,
                        aggiornati:
                        <strong><?php echo esc_html((string) ($_GET['updated'] ?? '0')); ?></strong>,
                        rimossi:
                        <strong><?php echo esc_html((string) ($_GET['deleted'] ?? '0')); ?></strong>.
                    </p>
                </div>

            <?php endif; ?>

            <h2>Sincronizzazione automatica</h2>

            <p>
                Il feed viene aggiornato automaticamente ogni giorno
                alle 03:00 (ora di Roma). Il plugin sincronizza tutti
                gli immobili ogni giorno alle 04:00 UTC, creando i
                nuovi annunci, aggiornando quelli esistenti e
                rimuovendo quelli non più presenti nel feed.
            </p>

            <form
                method="post"
                action="<?php echo esc_url(
                            admin_url('admin-post.php')
                        ); ?>">
                <input
                    type="hidden"
                    name="action"
                    value="getrix_sync_all">

                <?php wp_nonce_field('getrix_sync_all'); ?>

                <?php
                submit_button(
                    'Esegui sincronizzazione completa ora',
                    'secondary'
                );
                ?>
            </form>

            <hr>

            <h2>Sincronizzazione di un singolo immobile</h2>

            <form
                method="post"
                action="<?php echo esc_url(
                            admin_url('admin-post.php')
                        ); ?>">
                <input
                    type="hidden"
                    name="action"
                    value="getrix_sync_one">

                <?php
                wp_nonce_field(
                    'getrix_sync_one'
                );
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="getrix_id">
                                ID Immobile Getrix
                            </label>
                        </th>

                        <td>
                            <input
                                type="text"
                                name="getrix_id"
                                id="getrix_id"
                                class="regular-text"
                                required>

                            <p class="description">
                                Inserisci l'IDImmobile
                                presente nel feed.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php
                submit_button(
                    'Sincronizza immobile'
                );
                ?>
            </form>
        </div>
<?php
    }

    public function syncOne(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Non hai i permessi necessari.'
            );
        }

        check_admin_referer(
            'getrix_sync_one'
        );

        $getrixId = sanitize_text_field(
            wp_unslash(
                $_POST['getrix_id'] ?? ''
            )
        );

        if ($getrixId === '') {
            wp_die(
                'ID Immobile non valido.'
            );
        }

        try {
            $manager = \GetrixSync\Core\Plugin::container()
                ->get(SyncManager::class);

            $result = $manager->syncOne(
                $getrixId
            );
        } catch (\Throwable $exception) {
            wp_die(
                esc_html(
                    $exception->getMessage()
                )
            );
        }

        $url = add_query_arg(
            [
                'page' => 'getrix-sync',
                'getrix_sync' => 'success',
                'post_id' => $result['post_id'],
                'created' => $result['created'] ? '1' : '0',
            ],
            admin_url('tools.php')
        );

        wp_safe_redirect($url);

        exit;
    }

    public function syncAll(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'Non hai i permessi necessari.'
            );
        }

        check_admin_referer('getrix_sync_all');

        // A full sync downloads and validates the whole feed, which
        // can take a while on large catalogs.
        if (!defined('GETRIX_SYNC_MANUAL_RUN')) {
            define('GETRIX_SYNC_MANUAL_RUN', true);
        }

        set_time_limit(0);

        try {
            $manager = \GetrixSync\Core\Plugin::container()
                ->get(SyncManager::class);

            $result = $manager->syncAndPrune();
        } catch (\Throwable $exception) {
            wp_die(
                esc_html(
                    $exception->getMessage()
                )
            );
        }

        $url = add_query_arg(
            [
                'page' => 'getrix-sync',
                'getrix_sync_all' => 'success',
                'total' => $result['total'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'deleted' => $result['deleted'],
            ],
            admin_url('tools.php')
        );

        wp_safe_redirect($url);

        exit;
    }
}
