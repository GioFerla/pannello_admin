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

function validate_event_payload(array $input): array
{
    $errors = [];

    $nome = trim($input['name'] ?? '');
    $descrizione = trim($input['description'] ?? '');
    $ambito = trim($input['category'] ?? ($input['ambito'] ?? ''));

    if ($nome === '' || strlen($nome) < 3) {
        $errors[] = 'Il nome deve avere almeno 3 caratteri.';
    }
    if ($descrizione === '' || strlen($descrizione) < 10) {
        $errors[] = 'La descrizione deve avere almeno 10 caratteri.';
    }
    if ($ambito === '') {
        $errors[] = "L'ambito è obbligatorio.";
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

    $sedeNome = trim($input['sede_nome'] ?? '');
    $sedeVia = trim($input['sede_via'] ?? '');
    $sedeCitta = trim($input['sede_citta'] ?? '');
    $sedeProvincia = trim($input['sede_provincia'] ?? '');
    foreach ([
        'Nome sede' => $sedeNome,
        'Indirizzo sede' => $sedeVia,
        'Città' => $sedeCitta,
        'Provincia' => $sedeProvincia,
    ] as $label => $value) {
        if ($value === '') {
            $errors[] = $label . ' è obbligatorio.';
        }
    }

    $contattoEmail = trim($input['contatto_email'] ?? '');
    $contattoTelefono = trim($input['contatto_telefono'] ?? '');
    if ($contattoEmail === '' || !filter_var($contattoEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Inserisci un indirizzo email valido.';
    }

    $multimedia = [];
    $mediaNomi = $input['media_nome'] ?? [];
    $mediaTipo = $input['media_tipo'] ?? [];
    $mediaUrl = $input['media_url'] ?? [];
    $mediaRows = max(count($mediaNomi), count($mediaTipo), count($mediaUrl));
    for ($i = 0; $i < $mediaRows; $i++) {
        $nomeMedia = trim($mediaNomi[$i] ?? '');
        $tipo = trim($mediaTipo[$i] ?? '');
        $url = trim($mediaUrl[$i] ?? '');
        if ($nomeMedia === '' && $tipo === '' && $url === '') {
            continue;
        }
        if ($nomeMedia === '' || $tipo === '' || $url === '') {
            $errors[] = 'Ogni media richiede nome, tipo e URL.';
            continue;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Inserisci un URL valido per i contenuti multimediali.';
            continue;
        }
        $multimedia[] = [
            'nome' => $nomeMedia,
            'tipo' => $tipo,
            'url' => $url,
        ];
    }

    $payload = [
        'nome' => $nome,
        'descrizione' => $descrizione,
        'ambito' => $ambito,
        'data_inizio' => $start,
        'data_fine' => $end,
        'sede' => [
            'nome' => $sedeNome,
            'via' => $sedeVia,
            'citta' => $sedeCitta,
            'provincia' => $sedeProvincia,
        ],
        'contatto' => [
            'email' => $contattoEmail,
            'telefono' => $contattoTelefono,
        ],
        'multimedia' => $multimedia,
    ];

    return [$errors, $payload];
}

function get_or_create_ambito(PDO $pdo, string $nome): int
{
    $stmt = $pdo->prepare('SELECT idAmbito FROM AMBITO WHERE nome = :nome LIMIT 1');
    $stmt->execute(['nome' => $nome]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        return (int) $existing;
    }
    $insert = $pdo->prepare('INSERT INTO AMBITO (nome) VALUES (:nome)');
    $insert->execute(['nome' => $nome]);
    return (int) $pdo->lastInsertId();
}

function list_events(): array
{
    $pdo = db();
    $stmt = $pdo->query(
        'SELECT e.idEvento AS id, e.nome, e.descrizione, e.dataOraInizio AS data_inizio, e.dataOraFine AS data_fine, ' .
        'e.dataOraPubblicazione AS data_pubblicazione, a.nome AS ambito, ' .
        's.nome AS sede_nome, s.via, s.citta, s.provincia, c.email, c.telefono, ' .
        '(SELECT COUNT(*) FROM EVENTO_MULTIMEDIA em WHERE em.idEvento = e.idEvento) AS media_count ' .
        'FROM EVENTO e ' .
        'LEFT JOIN AMBITO a ON e.idAmbito = a.idAmbito ' .
        'LEFT JOIN SEDE s ON e.idSede = s.idSede ' .
        'LEFT JOIN CONTATTO c ON e.idContatto = c.idContatto ' .
        'ORDER BY e.dataOraInizio ASC'
    );
    return $stmt->fetchAll();
}

function fetch_event(string $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT e.idEvento AS id, e.nome, e.descrizione, e.dataOraInizio AS data_inizio, e.dataOraFine AS data_fine, ' .
        'e.dataOraPubblicazione AS data_pubblicazione, e.idAmbito, e.idSede, e.idContatto, a.nome AS ambito, ' .
        's.nome AS sede_nome, s.via, s.citta, s.provincia, c.email, c.telefono ' .
        'FROM EVENTO e ' .
        'LEFT JOIN AMBITO a ON e.idAmbito = a.idAmbito ' .
        'LEFT JOIN SEDE s ON e.idSede = s.idSede ' .
        'LEFT JOIN CONTATTO c ON e.idContatto = c.idContatto ' .
        'WHERE e.idEvento = :id'
    );
    $stmt->execute(['id' => $id]);
    $event = $stmt->fetch();
    if (!$event) {
        return null;
    }

    $mediaStmt = $pdo->prepare(
        'SELECT m.idMultimedia, m.nome, m.tipoFile, m.url ' .
        'FROM MULTIMEDIA m ' .
        'INNER JOIN EVENTO_MULTIMEDIA em ON em.idMultimedia = m.idMultimedia ' .
        'WHERE em.idEvento = :id ' .
        'ORDER BY m.idMultimedia'
    );
    $mediaStmt->execute(['id' => $id]);

    return [
        'evento' => $event,
        'multimedia' => $mediaStmt->fetchAll(),
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

        $ambitoId = get_or_create_ambito($pdo, $payload['ambito']);

        $stmt = $pdo->prepare('INSERT INTO SEDE (nome, via, citta, provincia) VALUES (:nome, :via, :citta, :provincia)');
        $stmt->execute($payload['sede']);
        $sedeId = (int) $pdo->lastInsertId();

        $contStmt = $pdo->prepare('INSERT INTO CONTATTO (email, telefono) VALUES (:email, :telefono)');
        $contStmt->execute($payload['contatto']);
        $contattoId = (int) $pdo->lastInsertId();

        $eventStmt = $pdo->prepare(
            'INSERT INTO EVENTO (nome, descrizione, dataOraInizio, dataOraFine, dataOraPubblicazione, idAmbito, idSede, idContatto) ' .
            'VALUES (:nome, :descrizione, :data_inizio, :data_fine, :data_pubblicazione, :idAmbito, :idSede, :idContatto)'
        );
        $eventStmt->execute([
            'nome' => $payload['nome'],
            'descrizione' => $payload['descrizione'],
            'data_inizio' => $payload['data_inizio'],
            'data_fine' => $payload['data_fine'],
            'data_pubblicazione' => date('Y-m-d H:i:s'),
            'idAmbito' => $ambitoId,
            'idSede' => $sedeId,
            'idContatto' => $contattoId,
        ]);
        $eventId = (int) $pdo->lastInsertId();

        if (!empty($payload['multimedia'])) {
            $mediaInsert = $pdo->prepare('INSERT INTO MULTIMEDIA (nome, tipoFile, url) VALUES (:nome, :tipoFile, :url)');
            $linkInsert = $pdo->prepare('INSERT INTO EVENTO_MULTIMEDIA (idEvento, idMultimedia) VALUES (:idEvento, :idMultimedia)');
            foreach ($payload['multimedia'] as $m) {
                $mediaInsert->execute([
                    'nome' => $m['nome'],
                    'tipoFile' => $m['tipo'],
                    'url' => $m['url'],
                ]);
                $mediaId = (int) $pdo->lastInsertId();
                $linkInsert->execute([
                    'idEvento' => $eventId,
                    'idMultimedia' => $mediaId,
                ]);
            }
        }

        $pdo->commit();
        return [[], (string) $eventId];
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

    [$errors, $payload] = validate_event_payload($input);
    if (!empty($errors)) {
        return [$errors, null];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $ambitoId = get_or_create_ambito($pdo, $payload['ambito']);

        $pdo->prepare('UPDATE SEDE SET nome = :nome, via = :via, citta = :citta, provincia = :provincia WHERE idSede = :id')
            ->execute([
                'nome' => $payload['sede']['nome'],
                'via' => $payload['sede']['via'],
                'citta' => $payload['sede']['citta'],
                'provincia' => $payload['sede']['provincia'],
                'id' => $existing['evento']['idSede'],
            ]);

        $pdo->prepare('UPDATE CONTATTO SET email = :email, telefono = :telefono WHERE idContatto = :id')
            ->execute([
                'email' => $payload['contatto']['email'],
                'telefono' => $payload['contatto']['telefono'],
                'id' => $existing['evento']['idContatto'],
            ]);

        $pdo->prepare(
            'UPDATE EVENTO SET nome = :nome, descrizione = :descrizione, dataOraInizio = :data_inizio, ' .
            'dataOraFine = :data_fine, idAmbito = :idAmbito WHERE idEvento = :id'
        )->execute([
            'nome' => $payload['nome'],
            'descrizione' => $payload['descrizione'],
            'data_inizio' => $payload['data_inizio'],
            'data_fine' => $payload['data_fine'],
            'idAmbito' => $ambitoId,
            'id' => $id,
        ]);

        $mediaIds = $pdo->prepare('SELECT idMultimedia FROM EVENTO_MULTIMEDIA WHERE idEvento = :id');
        $mediaIds->execute(['id' => $id]);
        $ids = $mediaIds->fetchAll(PDO::FETCH_COLUMN);

        $pdo->prepare('DELETE FROM EVENTO_MULTIMEDIA WHERE idEvento = :id')->execute(['id' => $id]);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $deleteMedia = $pdo->prepare('DELETE FROM MULTIMEDIA WHERE idMultimedia IN (' . $placeholders . ')');
            $deleteMedia->execute($ids);
        }

        if (!empty($payload['multimedia'])) {
            $mediaInsert = $pdo->prepare('INSERT INTO MULTIMEDIA (nome, tipoFile, url) VALUES (:nome, :tipoFile, :url)');
            $linkInsert = $pdo->prepare('INSERT INTO EVENTO_MULTIMEDIA (idEvento, idMultimedia) VALUES (:idEvento, :idMultimedia)');
            foreach ($payload['multimedia'] as $m) {
                $mediaInsert->execute([
                    'nome' => $m['nome'],
                    'tipoFile' => $m['tipo'],
                    'url' => $m['url'],
                ]);
                $mediaId = (int) $pdo->lastInsertId();
                $linkInsert->execute([
                    'idEvento' => $id,
                    'idMultimedia' => $mediaId,
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
    $pdo = db();
    try {
        $pdo->beginTransaction();

        $mediaIds = $pdo->prepare('SELECT idMultimedia FROM EVENTO_MULTIMEDIA WHERE idEvento = :id');
        $mediaIds->execute(['id' => $id]);
        $ids = $mediaIds->fetchAll(PDO::FETCH_COLUMN);

        $pdo->prepare('DELETE FROM EVENTO_MULTIMEDIA WHERE idEvento = :id')->execute(['id' => $id]);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $deleteMedia = $pdo->prepare('DELETE FROM MULTIMEDIA WHERE idMultimedia IN (' . $placeholders . ')');
            $deleteMedia->execute($ids);
        }

        $delete = $pdo->prepare('DELETE FROM EVENTO WHERE idEvento = :id');
        $delete->execute(['id' => $id]);

        $pdo->commit();
        return $delete->rowCount() > 0;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}
