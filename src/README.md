# Gestione Media nel Gestionale

Questo gestionale permette di associare contenuti multimediali (immagini, video, link, ecc.) agli eventi. Di seguito trovi una guida dettagliata su come funziona l’implementazione dei media, con esempi di codice e spiegazioni.

## 1. Struttura Database

I media sono gestiti tramite due tabelle principali:

- `MULTIMEDIA`: contiene i dati dei file (nome, tipo, url)
- `EVENTO_MULTIMEDIA`: collega i media agli eventi

```sql
CREATE TABLE MULTIMEDIA (
  idMultimedia INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255),
  tipoFile VARCHAR(50),
  url TEXT
);

CREATE TABLE EVENTO_MULTIMEDIA (
  idEvento INT,
  idMultimedia INT,
  PRIMARY KEY (idEvento, idMultimedia)
);
```

## 2. Inserimento Media

Quando crei o modifichi un evento, puoi aggiungere uno o più media:

```php
// Esempio di array media
$media = [
  ['nome' => 'Locandina', 'tipo' => 'image/png', 'url' => 'https://...'],
  ['nome' => 'Video promo', 'tipo' => 'video/mp4', 'url' => 'https://...'],
  ['nome' => 'YouTube', 'tipo' => 'video/youtube', 'url' => 'https://youtu.be/abcd1234']
];
```

Nel form di modifica evento:
```html
<input type="text" name="media_nome[]" value="Locandina">
<input type="text" name="media_tipo[]" value="image/png">
<input type="url" name="media_url[]" value="https://...">
```

## 3. Validazione e Salvataggio

Nel backend, i media vengono validati e salvati:
```php
foreach ($media as $m) {
  if ($m['nome'] && $m['tipo'] && filter_var($m['url'], FILTER_VALIDATE_URL)) {
    // Inserimento in MULTIMEDIA e collegamento a EVENTO_MULTIMEDIA
  }
}
```

## 4. Visualizzazione Media nella Dashboard

Nella dashboard, i media associati a ciascun evento vengono mostrati con una preview:

- Immagini: thumbnail
- Video: player
- YouTube: link
- Altri tipi: link

```php
foreach ($media as $m) {
  $type = strtolower($m['tipoFile'] ?? '');
  $url = $m['url'] ?? '';
  if (preg_match('/(?:youtu.be\/|youtube.com\/(?:watch\?v=|embed\/|v\/))([\w-]+)/', $url)) {
    echo '<a href="'.htmlspecialchars($url).'" target="_blank">YouTube</a>';
  } elseif (strpos($type, 'image') !== false) {
    echo '<img src="'.htmlspecialchars($url).'" style="max-width:60px;max-height:60px;">';
  } elseif (strpos($type, 'video') !== false) {
    echo '<video src="'.htmlspecialchars($url).'" style="max-width:60px;max-height:60px;" controls></video>';
  } elseif ($url) {
    echo '<a href="'.htmlspecialchars($url).'" target="_blank">'.htmlspecialchars($m['nome']).'</a>';
  }
}
```

## 5. Come aggiungere nuovi media

- Vai su "Modifica evento"
- Compila nome, tipo e URL per ogni media
- Salva: il media sarà collegato all’evento e visibile in dashboard

## 6. Note

- I media vengono validati (nome, tipo, URL)
- I link YouTube sono mostrati come link, non come embed
- Puoi aggiungere qualsiasi tipo di file, basta specificare il tipo e l’URL

---

Per dubbi o personalizzazioni, consulta il codice in `src/admin/dashboard.php` e `src/includes/data.php`.
