<?php

declare(strict_types=1);

namespace GetrixSync\WordPress;

use GetrixSync\Core\Container;
use GetrixSync\Core\ServiceProvider;
use GetrixSync\Support\Config;
use GetrixSync\Sync\SyncAlreadyRunningException;
use GetrixSync\Sync\SyncManager;
use GetrixSync\WordPress\PropertyMeta;
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

            <hr>

            <h2>Diagnostica</h2>

            <p>
                Strumento di sola lettura: mostra esattamente cosa
                vede il plugin nel database per un dato
                <code>getrix_id</code>, senza scrivere nulla. Utile
                per capire perché una sincronizzazione crea un nuovo
                post invece di aggiornare quello esistente.
            </p>

            <form method="get">
                <input
                    type="hidden"
                    name="page"
                    value="getrix-sync">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="diag_getrix_id">
                                ID Immobile Getrix da controllare
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="diag_getrix_id"
                                id="diag_getrix_id"
                                class="regular-text"
                                value="<?php
                                    echo esc_attr(
                                        (string) (
                                            $_GET['diag_getrix_id']
                                            ?? ''
                                        )
                                    );
                                ?>">
                        </td>
                    </tr>
                </table>

                <?php submit_button('Controlla'); ?>
            </form>

            <?php
            $diagId = trim(
                (string) ($_GET['diag_getrix_id'] ?? '')
            );

            if ($diagId !== '') {
                $this->renderDiagnostics($diagId);
            }
            ?>
        </div>
<?php
    }

    private function renderDiagnostics(string $getrixId): void
    {
        global $wpdb;

        $postType = (string) Config::get(
            'post_type',
            'immobile'
        );

        $metaKey = PropertyMeta::GETRIX_ID;

        $query = $wpdb->prepare(
            "
            SELECT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p
                ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND pm.meta_value = %s
              AND p.post_type = %s
            ORDER BY pm.post_id ASC
            LIMIT 1
            ",
            $metaKey,
            $getrixId,
            $postType
        );

        $foundId = $wpdb->get_var($query);
        $dbError = $wpdb->last_error;

        // All rows carrying this getrix_id, REGARDLESS of post_type
        // or post_status -- this is what catches a post_type
        // mismatch or a post sitting in the trash.
        $allRows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    p.ID,
                    p.post_type,
                    p.post_status,
                    p.post_title,
                    pm.meta_value AS getrix_id
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p
                    ON p.ID = pm.post_id
                WHERE pm.meta_key = %s
                  AND pm.meta_value = %s
                ",
                $metaKey,
                $getrixId
            )
        );

        ?>
        <div class="notice notice-info">
            <h3>Risultato diagnostica per getrix_id =
                <code><?php echo esc_html($getrixId); ?></code>
            </h3>

            <table class="widefat striped" style="max-width: 900px;">
                <tbody>
                    <tr>
                        <th style="width: 320px;">Prefisso tabelle ($wpdb-&gt;prefix)</th>
                        <td><code><?php echo esc_html($wpdb->prefix); ?></code></td>
                    </tr>
                    <tr>
                        <th>Post type configurato (Config::get('post_type'))</th>
                        <td><code><?php echo esc_html($postType); ?></code></td>
                    </tr>
                    <tr>
                        <th>Post type effettivamente registrato?</th>
                        <td><?php echo post_type_exists($postType) ? 'sì' : '<strong style="color:red">NO -- non registrato in WordPress</strong>'; ?></td>
                    </tr>
                    <tr>
                        <th>Meta key usata per l'identità (PropertyMeta::GETRIX_ID)</th>
                        <td><code><?php echo esc_html($metaKey); ?></code></td>
                    </tr>
                    <tr>
                        <th>Query eseguita da findByGetrixId()</th>
                        <td><pre style="white-space: pre-wrap;"><?php echo esc_html($query); ?></pre></td>
                    </tr>
                    <tr>
                        <th>Risultato ($wpdb-&gt;get_var())</th>
                        <td>
                            <?php
                            echo $foundId === null
                                ? '<strong>NULL (nessun post trovato)</strong>'
                                : 'post ID ' . esc_html((string) $foundId);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Errore SQL ($wpdb-&gt;last_error)</th>
                        <td>
                            <?php
                            echo $dbError === ''
                                ? '(nessuno)'
                                : '<strong style="color:red">' . esc_html($dbError) . '</strong>';
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h4>
                Tutti i post nel database con questo getrix_id
                (qualsiasi post_type o stato, incluso il cestino)
            </h4>

            <?php if ($allRows === []) : ?>
                <p>Nessun post trovato con questo getrix_id, in nessun post_type.</p>
            <?php else : ?>
                <table class="widefat striped" style="max-width: 900px;">
                    <thead>
                        <tr>
                            <th>Post ID</th>
                            <th>post_type</th>
                            <th>post_status</th>
                            <th>Titolo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRows as $row) : ?>
                            <tr<?php echo $row->post_type !== $postType ? ' style="background:#ffe8e8;"' : ''; ?>>
                                <td><?php echo esc_html((string) $row->ID); ?></td>
                                <td>
                                    <?php echo esc_html($row->post_type); ?>
                                    <?php if ($row->post_type !== $postType) : ?>
                                        <strong style="color:red">&larr; diverso da "<?php echo esc_html($postType); ?>"!</strong>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($row->post_status); ?></td>
                                <td><?php echo esc_html($row->post_title); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    Se in questa tabella compare più di una riga con
                    <code>post_type = <?php echo esc_html($postType); ?></code>,
                    sono i post duplicati già creati in precedenza:
                    andranno rimossi manualmente una volta risolta la
                    causa, la sincronizzazione da sola non li unisce.
                </p>
            <?php endif; ?>
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
        } catch (SyncAlreadyRunningException $exception) {
            wp_die(
                'Una sincronizzazione è già in corso. Attendi che '
                . 'finisca e riprova tra qualche minuto.'
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
        } catch (SyncAlreadyRunningException $exception) {
            wp_die(
                'Una sincronizzazione è già in corso (probabilmente '
                . 'quella automatica delle 04:00 UTC). Attendi che '
                . 'finisca e riprova tra qualche minuto.'
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
