<?php
require_once __DIR__ . '/config.php';

function data_config(): array
{
    static $cfg;
    if (!$cfg) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg;
}

function load_events(): array
{
    $cfg = data_config();
    if (!file_exists($cfg['data_file'])) {
        return [];
    }
    $raw = file_get_contents($cfg['data_file']);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_events(array $events): bool
{
    $cfg = data_config();
    $json = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($cfg['data_file'], $json, LOCK_EX) !== false;
}

function find_event(int $id): ?array
{
    foreach (load_events() as $event) {
        if ((int) ($event['id'] ?? 0) === $id) {
            return $event;
        }
    }
    return null;
}

function next_event_id(array $events): int
{
    $max = 0;
    foreach ($events as $event) {
        $max = max($max, (int) ($event['id'] ?? 0));
    }
    return $max + 1;
}

function validate_event(array $input, ?string $existingIssueDateTime = null): array
{
    $errors = [];

    $name = trim($input['name'] ?? '');
    if ($name === '' || strlen($name) < 3) {
        $errors[] = 'Il nome deve avere almeno 3 caratteri.';
    }

    $description = trim($input['description'] ?? '');
    if ($description === '' || strlen($description) < 10) {
        $errors[] = 'La descrizione deve avere almeno 10 caratteri.';
    }

    $scope = trim($input['scope'] ?? '');
    if ($scope === '') {
        $errors[] = 'Lo scopo è obbligatorio.';
    }

    $startInput = trim($input['startDateTime'] ?? '');
    $endInput = trim($input['endDateTime'] ?? '');
    $start = $startInput !== '' ? DateTime::createFromFormat('Y-m-d\TH:i', $startInput) : null;
    $end = $endInput !== '' ? DateTime::createFromFormat('Y-m-d\TH:i', $endInput) : null;

    if (!$start && $startInput !== '') {
        try {
            $start = new DateTime($startInput);
        } catch (Exception $e) {
            $start = null;
        }
    }

    if (!$end && $endInput !== '') {
        try {
            $end = new DateTime($endInput);
        } catch (Exception $e) {
            $end = null;
        }
    }

    if (!$start) {
        $errors[] = 'Data/ora di inizio non valida.';
    }
    if (!$end) {
        $errors[] = 'Data/ora di fine non valida.';
    }
    if ($start && $end && $start > $end) {
        $errors[] = 'La data di inizio deve precedere quella di fine.';
    }

    $issueInput = trim($input['issueDateTime'] ?? '') ?: $existingIssueDateTime;
    $issue = null;
    if ($issueInput) {
        try {
            $issue = new DateTime($issueInput);
        } catch (Exception $e) {
            $errors[] = 'Data di emissione non valida.';
        }
    } else {
        $issue = new DateTime();
    }

    $uri = trim($input['mediaResource_uri'] ?? '');
    if (!filter_var($uri, FILTER_VALIDATE_URL)) {
        $errors[] = 'Inserisci un URL valido per la risorsa media.';
    }

    $venueId = (int) ($input['venue_id'] ?? 1);
    $venueName = trim($input['venue_name'] ?? '');
    $street = trim($input['venue_street'] ?? '');
    $city = trim($input['venue_city'] ?? '');
    $province = trim($input['venue_province'] ?? '');
    $zipcode = trim($input['venue_zipcode'] ?? '');
    foreach ([
        'Nome luogo' => $venueName,
        'Via' => $street,
        'Città' => $city,
        'Provincia' => $province,
        'CAP' => $zipcode,
    ] as $label => $value) {
        if ($value === '') {
            $errors[] = $label . ' è obbligatorio.';
        }
    }

    $email = trim($input['contact_email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email di contatto non valida.';
    }

    $phone = trim($input['contact_phone'] ?? '');
    if (!preg_match('/^[0-9\s+\-]{7,}$/', $phone)) {
        $errors[] = 'Telefono di contatto non valido.';
    }

    $event = [
        'id' => isset($input['id']) ? (int) $input['id'] : null,
        'name' => $name,
        'description' => $description,
        'scope' => $scope,
        'startDateTime' => $start ? $start->format('c') : null,
        'endDateTime' => $end ? $end->format('c') : null,
        'issueDateTime' => $issue ? $issue->format('c') : null,
        'mediaResource' => [
            'uri' => $uri,
        ],
        'venue' => [
            'id' => $venueId,
            'name' => $venueName,
            'address' => [
                'street' => $street,
                'city' => $city,
                'province' => $province,
                'zipcode' => $zipcode,
            ],
        ],
        'contact' => [
            'email' => $email,
            'phone' => $phone,
        ],
    ];

    return [$errors, $event];
}

function update_event(array $events, int $id, array $newData): array
{
    foreach ($events as $index => $event) {
        if ((int) ($event['id'] ?? 0) === $id) {
            $events[$index] = $newData;
            return $events;
        }
    }
    return $events;
}

function delete_event(array $events, int $id): array
{
    return array_values(array_filter($events, function ($event) use ($id) {
        return (int) ($event['id'] ?? 0) !== $id;
    }));
}
