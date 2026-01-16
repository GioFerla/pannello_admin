<?php
require_once __DIR__ . '/db.php';

function normalize_datetime(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value) ?: DateTime::createFromFormat('Y-m-d H:i', $value);
    if (!$dt) {
        try {
            $dt = new DateTime($value);
        } catch (Exception $e) {
            return null;
        }
    }
    return $dt ? $dt->format('Y-m-d H:i:s') : null;
}

function validate_event_payload(array $input, ?string $forcedId = null): array
{
    $errors = [];

    $id = $forcedId ?: generate_event_id();
    $nome = trim($input['name'] ?? '');
    $descrizione = trim($input['description'] ?? '');
    $categoria = trim($input['category'] ?? '');
    $organizzatore = trim($input['organizzatore'] ?? '');

    if ($nome === '' || strlen($nome) < 3) {
        $errors[] = 'Il nome deve avere almeno 3 caratteri.';
    }
    if (strlen($nome) > 200) {
        $errors[] = 'Il nome non può superare i 200 caratteri.';
    }
    if ($descrizione === '' || strlen($descrizione) < 10) {
        $errors[] = 'La descrizione deve avere almeno 10 caratteri.';
    }
    if ($categoria === '') {
        $errors[] = 'La categoria è obbligatoria.';
    }
    if (strlen($categoria) > 100) {
        $errors[] = 'La categoria non può superare i 100 caratteri.';
    }
    if ($organizzatore === '') {
        $errors[] = "L'organizzatore è obbligatorio.";
    }
    if (strlen($organizzatore) > 150) {
        $errors[] = "L'organizzatore non può superare i 150 caratteri.";
    }

    $start = normalize_datetime($input['startDateTime'] ?? '');
    $end = normalize_datetime($input['endDateTime'] ?? '');
    if (!$start) {
        $errors[] = 'Data/ora di inizio non valida.';
    }
    if (!$end) {
        $errors[] = 'Data/ora di fine non valida.';
    }
    if ($start && $end && strtotime($start) > strtotime($end)) {
        $errors[] = 'La data di inizio deve precedere quella di fine.';
    }

    $via = trim($input['sede_via'] ?? '');
    $cap = trim($input['sede_cap'] ?? '');
    $paese = trim($input['sede_paese'] ?? '');
    foreach ([
        'Indirizzo della sede' => $via,
        'CAP' => $cap,
        'Paese' => $paese,
    ] as $label => $value) {
        if ($value === '') {
            $errors[] = $label . ' è obbligatorio.';
        }
    }
    if (strlen($via) > 255) {
        $errors[] = 'L\'indirizzo della sede non può superare i 255 caratteri.';
    }
    if ($cap !== '' && !preg_match('/^\d{5}$/', $cap)) {
        $errors[] = 'Il CAP deve essere composto da 5 cifre.';
    }
    if (strlen($paese) > 120) {
        $errors[] = 'Il paese non può superare i 120 caratteri.';
    }

    $accessibilita = [
        'rampe' => isset($input['rampe']) ? 1 : 0,
        'ascensori' => isset($input['ascensori']) ? 1 : 0,
        'posti_disabili' => (int) ($input['posti_disabili'] ?? 0),
    ];
    if ($accessibilita['posti_disabili'] < 0) {
        $errors[] = 'I posti riservati devono essere maggiori o uguali a zero.';
    }

    $tariffe = [];
    $tariffeTipo = $input['tariffe_tipo'] ?? [];
    $tariffePrezzo = $input['tariffe_prezzo'] ?? [];
    $tariffeValuta = $input['tariffe_valuta'] ?? [];
    $rows = max(count($tariffeTipo), count($tariffePrezzo), count($tariffeValuta));
    for ($i = 0; $i < $rows; $i++) {
        $tipo = trim($tariffeTipo[$i] ?? '');
        $prezzo = $tariffePrezzo[$i] ?? '';
        $valuta = trim($tariffeValuta[$i] ?? '');
        if ($tipo === '' && $prezzo === '' && $valuta === '') {
            continue;
        }
        if ($tipo === '' || $prezzo === '' || $valuta === '') {
            $errors[] = 'Ogni tariffa deve avere tipo, prezzo e valuta.';
            continue;
        }
        if (!is_numeric($prezzo) || (float) $prezzo < 0) {
            $errors[] = 'Il prezzo deve essere un numero maggiore o uguale a zero.';
            continue;
        }
        $tariffe[] = [
            'tipo' => $tipo,
            'prezzo' => number_format((float) $prezzo, 2, '.', ''),
            'valuta' => $valuta,
        ];
    }

    $orari = [];
    $orariGiorno = $input['orari_giorno'] ?? [];
    $orariApertura = $input['orari_apertura'] ?? [];
    $orariChiusura = $input['orari_chiusura'] ?? [];
    $orariRows = max(count($orariGiorno), count($orariApertura), count($orariChiusura));
    for ($i = 0; $i < $orariRows; $i++) {
        $giorno = trim($orariGiorno[$i] ?? '');
        $apertura = trim($orariApertura[$i] ?? '');
        $chiusura = trim($orariChiusura[$i] ?? '');
        if ($giorno === '' && $apertura === '' && $chiusura === '') {
            continue;
        }
        if ($giorno === '' || $apertura === '' || $chiusura === '') {
            $errors[] = 'Ogni orario deve includere giorno, apertura e chiusura.';
            continue;
        }
        // Validate date format
        $dt = DateTime::createFromFormat('Y-m-d', $giorno);
        if (!$dt || $dt->format('Y-m-d') !== $giorno) {
            $errors[] = 'Formato data non valido per il giorno. Usare YYYY-MM-DD.';
            continue;
        }
        // Validate time formats and compare
        $dtApertura = DateTime::createFromFormat('H:i', $apertura);
        $dtChiusura = DateTime::createFromFormat('H:i', $chiusura);
        if (!$dtApertura || $dtApertura->format('H:i') !== $apertura) {
            $errors[] = 'Formato ora non valido per apertura. Usare HH:MM.';
            continue;
        }
        if (!$dtChiusura || $dtChiusura->format('H:i') !== $chiusura) {
            $errors[] = 'Formato ora non valido per chiusura. Usare HH:MM.';
            continue;
        }
        if ($dtApertura >= $dtChiusura) {
            $errors[] = "L'ora di apertura deve precedere quella di chiusura.";
            continue;
        }
        $orari[] = [
            'giorno' => $giorno,
            'apertura' => $apertura,
            'chiusura' => $chiusura,
        ];
    }

    $multimedia = [];
    $mediaTipo = $input['media_tipo'] ?? [];
    $mediaUrl = $input['media_url'] ?? [];
    $mediaDescrizione = $input['media_descrizione'] ?? [];
    $mediaRows = max(count($mediaTipo), count($mediaUrl), count($mediaDescrizione));
    for ($i = 0; $i < $mediaRows; $i++) {
        $tipo = trim($mediaTipo[$i] ?? '');
        $url = trim($mediaUrl[$i] ?? '');
        $descr = trim($mediaDescrizione[$i] ?? '');
        if ($tipo === '' && $url === '' && $descr === '') {
            continue;
        }
        if ($tipo === '' || $url === '') {
            $errors[] = 'Ogni elemento media richiede tipo e URL.';
            continue;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Inserisci un URL valido per i contenuti multimediali.';
            continue;
        }
        $multimedia[] = [
            'tipo' => $tipo,
            'url' => $url,
            'descrizione' => $descr,
        ];
    }

    $payload = [
        'id' => $id,
        'nome' => $nome,
        'descrizione' => $descrizione,
        'categoria' => $categoria,
        'data_inizio' => $start,
        'data_fine' => $end,
        'organizzatore' => $organizzatore,
        'sede' => [
            'via' => $via,
            'cap' => $cap,
            'paese' => $paese,
        ],
        'accessibilita' => $accessibilita,
        'tariffe' => $tariffe,
        'orari' => $orari,
        'multimedia' => $multimedia,
    ];

    return [$errors, $payload];
}

function list_events(): array
{
    $pdo = db();
    $stmt = $pdo->query(
        'SELECT e.id, e.nome, e.categoria, e.data_inizio, e.data_fine, e.organizzatore, ' .
        's.via, s.cap, s.paese, ' .
        '(SELECT COUNT(*) FROM tariffa t WHERE t.evento_id = e.id) AS tariffe_count, ' .
        '(SELECT COUNT(*) FROM orario o WHERE o.evento_id = e.id) AS orari_count, ' .
        '(SELECT COUNT(*) FROM multimedia m WHERE m.evento_id = e.id) AS media_count ' .
        'FROM evento e ' .
        'LEFT JOIN ente en ON e.organizzatore = en.nome ' .
        'LEFT JOIN sede s ON en.id_indirizzo = s.id ' .
        'ORDER BY e.data_inizio ASC'
    );
    return $stmt->fetchAll();
}

function fetch_event(string $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT e.*, s.id AS sede_id, s.via, s.cap, s.paese ' .
        'FROM evento e ' .
        'LEFT JOIN ente en ON e.organizzatore = en.nome ' .
        'LEFT JOIN sede s ON en.id_indirizzo = s.id ' .
        'WHERE e.id = :id'
    );
    $stmt->execute(['id' => $id]);
    $event = $stmt->fetch();
    if (!$event) {
        return null;
    }

    $accStmt = $pdo->prepare('SELECT * FROM accessibilita WHERE id = :id');
    $accStmt->execute(['id' => $id]);
    $access = $accStmt->fetch() ?: [
        'rampe' => 0,
        'ascensori' => 0,
        'posti_disabili' => 0,
    ];

    $tariffe = $pdo->prepare('SELECT * FROM tariffa WHERE evento_id = :id ORDER BY id');
    $tariffe->execute(['id' => $id]);

    $orari = $pdo->prepare('SELECT * FROM orario WHERE evento_id = :id ORDER BY giorno, apertura');
    $orari->execute(['id' => $id]);

    $media = $pdo->prepare('SELECT * FROM multimedia WHERE evento_id = :id ORDER BY id');
    $media->execute(['id' => $id]);

    return [
        'evento' => $event,
        'accessibilita' => $access,
        'tariffe' => $tariffe->fetchAll(),
        'orari' => $orari->fetchAll(),
        'multimedia' => $media->fetchAll(),
    ];
}

function create_event(array $input): array
{
    [$errors, $payload] = validate_event_payload($input);
    if (!empty($errors)) {
        return [$errors, null];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO sede (via, cap, paese) VALUES (:via, :cap, :paese)');
        $stmt->execute($payload['sede']);
        $sedeId = (int) $pdo->lastInsertId();

        $enteStmt = $pdo->prepare('INSERT INTO ente (nome, id_indirizzo) VALUES (:nome, :id_indirizzo)');
        $enteStmt->execute([
            'nome' => $payload['organizzatore'],
            'id_indirizzo' => $sedeId,
        ]);

        $eventStmt = $pdo->prepare(
            'INSERT INTO evento (id, nome, descrizione, categoria, data_inizio, data_fine, organizzatore) ' .
            'VALUES (:id, :nome, :descrizione, :categoria, :data_inizio, :data_fine, :organizzatore)'
        );
        $eventStmt->execute([
            'id' => $payload['id'],
            'nome' => $payload['nome'],
            'descrizione' => $payload['descrizione'],
            'categoria' => $payload['categoria'],
            'data_inizio' => $payload['data_inizio'],
            'data_fine' => $payload['data_fine'],
            'organizzatore' => $payload['organizzatore'],
        ]);

        $accStmt = $pdo->prepare(
            'INSERT INTO accessibilita (id, rampe, ascensori, posti_disabili) VALUES (:id, :rampe, :ascensori, :posti)'
        );
        $accStmt->execute([
            'id' => $payload['id'],
            'rampe' => $payload['accessibilita']['rampe'],
            'ascensori' => $payload['accessibilita']['ascensori'],
            'posti' => $payload['accessibilita']['posti_disabili'],
        ]);

        if (!empty($payload['tariffe'])) {
            $tarStmt = $pdo->prepare(
                'INSERT INTO tariffa (evento_id, tipo, prezzo, valuta) VALUES (:evento_id, :tipo, :prezzo, :valuta)'
            );
            foreach ($payload['tariffe'] as $t) {
                $tarStmt->execute([
                    'evento_id' => $payload['id'],
                    'tipo' => $t['tipo'],
                    'prezzo' => $t['prezzo'],
                    'valuta' => $t['valuta'],
                ]);
            }
        }

        if (!empty($payload['orari'])) {
            $oraStmt = $pdo->prepare(
                'INSERT INTO orario (evento_id, giorno, apertura, chiusura) VALUES (:evento_id, :giorno, :apertura, :chiusura)'
            );
            foreach ($payload['orari'] as $o) {
                $oraStmt->execute([
                    'evento_id' => $payload['id'],
                    'giorno' => $o['giorno'],
                    'apertura' => $o['apertura'],
                    'chiusura' => $o['chiusura'],
                ]);
            }
        }

        if (!empty($payload['multimedia'])) {
            $mediaStmt = $pdo->prepare(
                'INSERT INTO multimedia (evento_id, tipo, url, descrizione) VALUES (:evento_id, :tipo, :url, :descrizione)'
            );
            foreach ($payload['multimedia'] as $m) {
                $mediaStmt->execute([
                    'evento_id' => $payload['id'],
                    'tipo' => $m['tipo'],
                    'url' => $m['url'],
                    'descrizione' => $m['descrizione'],
                ]);
            }
        }

        $pdo->commit();
        return [[], $payload['id']];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [["Errore durante il salvataggio: " . $e->getMessage()], null];
    }
}

function update_event_record(string $id, array $input): array
{
    $existing = fetch_event($id);
    if (!$existing) {
        return [["Evento non trovato."], null];
    }

    [$errors, $payload] = validate_event_payload($input, $id);
    if (!empty($errors)) {
        return [$errors, null];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $pdo->prepare('UPDATE sede SET via = :via, cap = :cap, paese = :paese WHERE id = :id')
            ->execute([
                'via' => $payload['sede']['via'],
                'cap' => $payload['sede']['cap'],
                'paese' => $payload['sede']['paese'],
                'id' => $existing['evento']['sede_id'],
            ]);

        $pdo->prepare('UPDATE ente SET nome = :nome WHERE nome = :old_nome')
            ->execute([
                'nome' => $payload['organizzatore'],
                'old_nome' => $existing['evento']['organizzatore'],
            ]);

        $pdo->prepare(
            'UPDATE evento SET nome = :nome, descrizione = :descrizione, categoria = :categoria, ' .
            'data_inizio = :data_inizio, data_fine = :data_fine, organizzatore = :organizzatore WHERE id = :id'
        )->execute([
            'nome' => $payload['nome'],
            'descrizione' => $payload['descrizione'],
            'categoria' => $payload['categoria'],
            'data_inizio' => $payload['data_inizio'],
            'data_fine' => $payload['data_fine'],
            'organizzatore' => $payload['organizzatore'],
            'id' => $id,
        ]);

        $pdo->prepare(
            'INSERT INTO accessibilita (id, rampe, ascensori, posti_disabili) VALUES (:id, :rampe, :ascensori, :posti) ' .
            'ON DUPLICATE KEY UPDATE rampe = VALUES(rampe), ascensori = VALUES(ascensori), posti_disabili = VALUES(posti_disabili)'
        )->execute([
            'id' => $id,
            'rampe' => $payload['accessibilita']['rampe'],
            'ascensori' => $payload['accessibilita']['ascensori'],
            'posti' => $payload['accessibilita']['posti_disabili'],
        ]);

        $pdo->prepare('DELETE FROM tariffa WHERE evento_id = :id')->execute(['id' => $id]);
        if (!empty($payload['tariffe'])) {
            $stmt = $pdo->prepare('INSERT INTO tariffa (evento_id, tipo, prezzo, valuta) VALUES (:evento_id, :tipo, :prezzo, :valuta)');
            foreach ($payload['tariffe'] as $t) {
                $stmt->execute([
                    'evento_id' => $id,
                    'tipo' => $t['tipo'],
                    'prezzo' => $t['prezzo'],
                    'valuta' => $t['valuta'],
                ]);
            }
        }

        $pdo->prepare('DELETE FROM orario WHERE evento_id = :id')->execute(['id' => $id]);
        if (!empty($payload['orari'])) {
            $stmt = $pdo->prepare('INSERT INTO orario (evento_id, giorno, apertura, chiusura) VALUES (:evento_id, :giorno, :apertura, :chiusura)');
            foreach ($payload['orari'] as $o) {
                $stmt->execute([
                    'evento_id' => $id,
                    'giorno' => $o['giorno'],
                    'apertura' => $o['apertura'],
                    'chiusura' => $o['chiusura'],
                ]);
            }
        }

        $pdo->prepare('DELETE FROM multimedia WHERE evento_id = :id')->execute(['id' => $id]);
        if (!empty($payload['multimedia'])) {
            $stmt = $pdo->prepare('INSERT INTO multimedia (evento_id, tipo, url, descrizione) VALUES (:evento_id, :tipo, :url, :descrizione)');
            foreach ($payload['multimedia'] as $m) {
                $stmt->execute([
                    'evento_id' => $id,
                    'tipo' => $m['tipo'],
                    'url' => $m['url'],
                    'descrizione' => $m['descrizione'],
                ]);
            }
        }

        $pdo->commit();
        return [[], $id];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [["Errore durante l'aggiornamento: " . $e->getMessage()], null];
    }
}

function delete_event_record(string $id): bool
{
    $meta = fetch_event($id);
    if (!$meta) {
        return false;
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM evento WHERE id = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM ente WHERE nome = :nome')->execute(['nome' => $meta['evento']['organizzatore']]);
        if (!empty($meta['evento']['sede_id'])) {
            $pdo->prepare('DELETE FROM sede WHERE id = :id')->execute(['id' => $meta['evento']['sede_id']]);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}
