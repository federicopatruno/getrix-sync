# Modifiche: rendering "Immobile" indipendente dal tema + stile base

Estrai questo zip nella root del plugin `getrix-sync` (sovrascrivendo/creando
i file elencati sotto), poi:

```bash
git add src/Core/Plugin.php src/WordPress/TemplateLoader.php templates/ assets/
git commit -m "Add theme-agnostic template loader for Immobile CPT + base stylesheet"
git push
```

## File inclusi

- `src/Core/Plugin.php` — **modificato**: registra il nuovo `TemplateLoader`
  nel bootstrap del plugin (senza questa riga il loader esisteva ma non
  veniva mai eseguito).
- `src/WordPress/TemplateLoader.php` — **nuovo**: hook su `template_include`
  per servire `single-immobile.php` / `archive-immobile.php` su qualsiasi
  tema attivo; se il tema (o child theme) fornisce un proprio file con lo
  stesso nome, quello ha sempre la priorità. Aggiunge anche l'enqueue
  condizionato del CSS via `wp_enqueue_scripts`.
- `templates/single-immobile.php` — **nuovo**: template di fallback per la
  scheda del singolo immobile (titolo, prezzo, galleria, caratteristiche,
  descrizione, mappa).
- `templates/archive-immobile.php` — **nuovo**: template di fallback per la
  griglia dell'elenco immobili, con paginazione.
- `assets/getrix-immobile.css` — **nuovo**: foglio di stile di base per i
  due template sopra, caricato solo sulle pagine immobile. Tutte le regole
  sono sotto `.getrix-immobile` / `.getrix-immobile-card`, quindi un tema
  può sovrascriverle con selettori più specifici, oppure disattivarlo del
  tutto da `functions.php`:

  ```php
  add_action('wp_dequeue_style', function () {
      wp_dequeue_style('getrix-immobile');
  });
  ```

## Note

- Entrambi i template chiamano `get_header()` / `get_footer()`, quindi
  ereditano sempre header, footer, menu e font del tema attivo.
- Se in futuro un tema (o un child theme) vuole personalizzare del tutto il
  markup, basta che crei un proprio `single-immobile.php` o
  `archive-immobile.php` nella root del tema: `TemplateLoader` lo rileverà
  automaticamente e userà quello al posto del fallback del plugin.
