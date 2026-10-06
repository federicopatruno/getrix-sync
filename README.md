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
protetta da un lock (`SyncLock`). Se una sincronizzazione è già in
corso, un secondo tentativo che parte nel frattempo (es. il cron delle
04:00 UTC che si sovrappone a un click manuale, o un doppio click sul
pulsante "Sincronizza immobile") non esegue alcuna operazione invece
di rischiare di creare un post duplicato per lo stesso `getrix_id`:
"controlla se esiste, altrimenti crea" non è atomico, quindi due
esecuzioni realmente concorrenti potrebbero altrimenti controllare
entrambe "non esiste" prima che una delle due abbia scritto.

Il lock scrive direttamente su `wp_options` con un `INSERT` grezzo
(`$wpdb->insert()`, non `add_option()`): dalla 6.4 in poi `add_option()`
usa internamente `INSERT ... ON DUPLICATE KEY UPDATE`, quindi due
chiamate quasi simultanee possono restituire entrambe `true` e non è
una primitiva di mutex affidabile. Un `INSERT` semplice sulla colonna
`option_name` (che ha un vincolo `UNIQUE` nello schema di WordPress)
è invece garantito atomico dal database stesso: se due processi
tentano l'insert nello stesso istante, uno solo riesce, l'altro fallisce
con un errore di chiave duplicata, rilevabile in modo affidabile. Il
lock ha un TTL di sicurezza (10 minuti di default, `sync.lock_ttl`) che
lo rilascia automaticamente se un run precedente è terminato in modo
anomalo (crash, timeout) senza rilasciarlo.

### Sincronizzazione manuale

In **Strumenti → Getrix Sync** è disponibile:

- un pulsante per lanciare subito una sincronizzazione completa
  (stessa logica del cron: crea, aggiorna, rimuove);
- un form per sincronizzare un singolo immobile dato il suo
  `IDImmobile`, utile per il debug.

## Immagine in evidenza

Ad ogni sincronizzazione, il plugin imposta automaticamente
l'immagine in evidenza (featured image) del post `immobile`,
scaricandola dalla foto con `posizione` più bassa nel feed (la
copertina) e allegandola alla media library di WordPress --
l'immagine in evidenza nativa di WordPress richiede un allegato
reale, non può puntare a un URL esterno.

Per evitare di riscaricare la stessa foto ad ogni sync giornaliero,
il plugin tiene traccia (nel meta `getrix_featured_image_source`)
di quale immagine Getrix è attualmente impostata come copertina, e
la riscarica solo se cambia nel feed. L'intera galleria (tutte le
foto, come URL esterni) resta invece nel campo ripetitore ACF
`immagini_getrix`, gestita separatamente e non scaricata.

Disattivabile impostando `sync.download_images` a `false` in
`config/plugin.php`.

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

## Nota sulla chiave meta di identità

`_getrix_id` (con underscore iniziale) è la convenzione WordPress
standard per il meta "protetto" (nascosto dai Custom Fields). Il
plugin usa invece `getrix_id` (senza underscore) in
`PropertyMeta::GETRIX_ID`, per allinearsi ai dati già presenti nel
database di produzione. Se in futuro il CPT `immobile` viene
ricreato da zero su un sito nuovo, questa scelta resta valida
comunque; l'unica cosa da evitare è modificare questa costante senza
anche migrare i meta già salvati, perché `PropertyRepository::
findByGetrixId()` la usa per decidere se creare o aggiornare un
post — un disallineamento tra la chiave usata dal codice e quella
realmente salvata nel database fa sì che ogni sincronizzazione non
trovi mai il post già esistente e ne crei uno nuovo ad ogni run.

## Test

```bash
php tests/GetrixParserTest.php
php tests/GetrixPropertyMapperTest.php
php tests/manual-parse.php
```

(Non è presente PHPUnit: sono script eseguibili direttamente che
caricano il feed reale e stampano l'esito.)
