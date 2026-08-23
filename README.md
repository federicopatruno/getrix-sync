# Getrix Sync

Plugin WordPress che sincronizza un feed XML Getrix (schema
[`feed_3_1_0.xsd`](http://feed.getrix.it/xml/feed_3_1_0.xsd)) con un
Custom Post Type `immobile` gestito tramite Advanced Custom Fields
(ACF Pro).

## Requisiti

- PHP 8.2+
- WordPress 6.8+
- ACF Pro attivo (usa `acf_add_local_field_group()` / `update_field()`)
- Estensione PHP `DOMDocument` (per la validazione XSD)
- `composer install` per le dipendenze (`guzzlehttp/guzzle`,
  `monolog/monolog`, `nesbot/carbon`)

## Installazione

```bash
composer install --no-dev -o
```

Copia la cartella del plugin in `wp-content/plugins/getrix-sync` e
attivalo dal pannello di amministrazione.

## Configurazione

Tutta la configurazione vive in `config/plugin.php`:

```php
'feed' => [
    'url' => 'https://studiostilo.it/wp-content/feeds/....xml',
    'xsd_url' => 'http://feed.getrix.it/xml/feed_3_1_0.xsd',
],
'sync' => [
    'delete_missing' => true, // rimuove gli immobili non più nel feed
    'download_images' => true,
],
```

## Sincronizzazione automatica

Il feed sorgente viene rigenerato ogni giorno alle **03:00 (ora di
Roma)**. Il plugin registra un evento WP-Cron (hook
`getrix_sync_run`, vedi `cron_hook` in config) schedulato ogni giorno
alle **04:00 UTC** (un'ora di margine rispetto alla generazione del
feed). Ad ogni esecuzione:

1. scarica il feed XML;
2. lo valida contro l'XSD Getrix;
3. crea o aggiorna un post `immobile` per ogni `<Immobile>` presente
   (identificato dall'attributo `IDImmobile`, salvato nel meta
   `_getrix_id`);
4. se `sync.delete_missing` è `true`, elimina definitivamente
   (`wp_delete_post($id, true)`) ogni post `immobile` il cui
   `_getrix_id` non è più presente nel feed.

La schedulazione viene creata all'attivazione del plugin
(`SyncCronProvider::activate()`), rimossa alla disattivazione
(`SyncCronProvider::deactivate()`) e "auto-guarita" ad ogni `init` nel
caso l'evento sia stato cancellato senza passare dalla disattivazione
(es. dopo un deploy via git).

> WP-Cron viene innescato dal traffico del sito: su siti a basso
> traffico è consigliato lasciare attivo il comportamento di default
> e/o affiancare un vero cron di sistema che chiami `wp-cron.php`
> periodicamente, per garantire puntualità.

### Concorrenza

Ogni sincronizzazione (cron, pulsante manuale, singolo immobile) è
protetta da un lock (`SyncLock`, basato su `wp_options` con
`add_option()`, atomico a livello di database). Se una sincronizzazione
è già in corso, un secondo tentativo che parte nel frattempo (es. il
cron delle 04:00 UTC che si sovrappone a un click manuale) non esegue
alcuna operazione invece di rischiare di creare un post duplicato per
lo stesso `getrix_id`: "controlla se esiste, altrimenti crea" non è
atomico, quindi due esecuzioni realmente concorrenti potrebbero
altrimenti controllare entrambe "non esiste" prima che una delle due
abbia scritto. Il lock ha un TTL di sicurezza (10 minuti di default,
`sync.lock_ttl`) che lo rilascia automaticamente se un run precedente
è terminato in modo anomalo (crash, timeout) senza rilasciarlo.

### Sincronizzazione manuale

In **Strumenti → Getrix Sync** è disponibile:

- un pulsante per lanciare subito una sincronizzazione completa
  (stessa logica del cron: crea, aggiorna, rimuove);
- un form per sincronizzare un singolo immobile dato il suo
  `IDImmobile`, utile per il debug.

## Architettura

```
src/
  Core/       bootstrap minimale (Container + ServiceProvider)
  Feed/       download, parsing e validazione XSD del feed
  Domain/     mapping dei dati Getrix -> oggetto Property
  Acf/        definizione dei campi ACF del CPT "immobile"
  WordPress/  CPT, meta, repository (CRUD + prune), ACF writer, admin UI
  Sync/       SyncManager (orchestrazione) + SyncCronProvider (WP-Cron)
```

Il CPT `immobile` espone attualmente un sottoinsieme curato dei campi
dello schema Getrix (identificazione, localizzazione, dati
commerciali, date, descrizioni multilingua, immagini). Gli script
`debug-acf-factory.php` / `debug-field-map-acf.php` contengono un
generatore sperimentale che deriva i gruppi di campi ACF direttamente
dallo XSD, utile per estendere la copertura ai blocchi
`Residenziale` / `Commerciale` / `Terreno` in modo incrementale senza
scrivere a mano ogni singolo campo.

## Test

```bash
php tests/GetrixParserTest.php
php tests/GetrixPropertyMapperTest.php
php tests/manual-parse.php
```

(Non è presente PHPUnit: sono script eseguibili direttamente che
caricano il feed reale e stampano l'esito.)
