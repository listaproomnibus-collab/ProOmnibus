<?php
require_once __DIR__ . '/common.php';
po_start_session();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $pdo = po_pdo();
    $isAdmin = !empty($_SESSION['po_is_admin']);
    $sql = 'SELECT id, title, subject, publisher, class_level AS classLevel,
                   condition_label AS `condition`, notes, seller_name AS sellerName,
                   phone, image_data AS imageData, created_at AS createdAt,
                   status, taken_at AS takenAt
            FROM pro_omnibus_books';
    if (!$isAdmin) {
        $sql .= " WHERE status = 'available'";
    }
    $sql .= ' ORDER BY created_at DESC';
    try {
        $stmt = $pdo->query($sql);
        po_json_response(200, $stmt->fetchAll());
    } catch (Throwable $e) {
        error_log('Pro Omnibus books GET failed: ' . $e->getMessage());
        po_json_response(500, ['error' => 'Non è stato possibile caricare il catalogo.']);
    }
}

if ($method === 'POST') {
    $payload = po_input();
    // Limita gli invii ripetuti nella stessa sessione.
    $now = time();
    $_SESSION['po_book_post_times'] = array_values(array_filter(
        $_SESSION['po_book_post_times'] ?? [],
        static fn($time) => is_int($time) && $time > $now - 600
    ));
    if (count($_SESSION['po_book_post_times']) >= 5) {
        po_json_response(429, ['error' => 'Hai pubblicato troppi annunci. Riprova tra qualche minuto.']);
    }

    $title = po_text($payload['title'] ?? null, 200);
    $subject = po_text($payload['subject'] ?? null, 80);
    $publisher = po_text($payload['publisher'] ?? '', 160);
    $classLevel = po_text($payload['classLevel'] ?? null, 8);
    $condition = po_text($payload['condition'] ?? null, 20);
    $notes = po_text($payload['notes'] ?? '', 1500);
    $sellerName = po_text($payload['sellerName'] ?? null, 80);
    $phone = po_text($payload['phone'] ?? null, 30);
    $imageData = $payload['imageData'] ?? '';
    if (!is_string($imageData)) $imageData = '';

    if ($title === '' || $subject === '' || $classLevel === '' || $sellerName === '' || $phone === '') {
        po_json_response(400, ['error' => 'Compila tutti i campi obbligatori.']);
    }
    if (!preg_match('/^[1-5][A-D]$/', $classLevel)) {
        po_json_response(400, ['error' => 'Seleziona una classe valida.']);
    }
    if (!in_array($condition, ['Ottime', 'Buone', 'Discrete'], true)) {
        po_json_response(400, ['error' => 'Seleziona una condizione valida.']);
    }
    if (!preg_match('/^[0-9 +()\-]{8,30}$/', $phone) || strlen(preg_replace('/\D/', '', $phone)) < 8) {
        po_json_response(400, ['error' => 'Inserisci un numero di telefono valido.']);
    }
    if (strlen($imageData) > 1000000 || ($imageData !== '' && !preg_match('#^data:image/jpeg;base64,[A-Za-z0-9+/=]+$#', $imageData))) {
        po_json_response(400, ['error' => 'La foto non è valida o è troppo grande. Riprova con un’immagine più leggera.']);
    }

    try {
        $id = bin2hex(random_bytes(12));
        $createdAt = date(DATE_ATOM);
        $stmt = po_pdo()->prepare('INSERT INTO pro_omnibus_books
            (id, title, subject, publisher, class_level, condition_label, notes, seller_name, phone, image_data, created_at, status, taken_at)
            VALUES (:id, :title, :subject, :publisher, :class_level, :condition_label, :notes, :seller_name, :phone, :image_data, :created_at, :status, NULL)');
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':subject' => $subject,
            ':publisher' => $publisher,
            ':class_level' => $classLevel,
            ':condition_label' => $condition,
            ':notes' => $notes,
            ':seller_name' => $sellerName,
            ':phone' => $phone,
            ':image_data' => $imageData,
            ':created_at' => $createdAt,
            ':status' => 'available',
        ]);
        $_SESSION['po_book_post_times'][] = $now;
        po_json_response(201, ['ok' => true, 'id' => $id]);
    } catch (Throwable $e) {
        error_log('Pro Omnibus books POST failed: ' . $e->getMessage());
        po_json_response(500, ['error' => 'Non è stato possibile pubblicare il libro. Controlla che il database sia stato configurato.']);
    }
}

if ($method === 'PATCH') {
    po_require_admin();
    $payload = po_input();
    $id = po_text($payload['id'] ?? null, 40);
    $status = isset($payload['status']) && is_string($payload['status']) ? $payload['status'] : '';
    if ($id === '' || !in_array($status, ['available', 'taken'], true)) {
        po_json_response(400, ['error' => 'Richiesta non valida.']);
    }
    try {
        $takenAt = $status === 'taken' ? date(DATE_ATOM) : null;
        $stmt = po_pdo()->prepare('UPDATE pro_omnibus_books SET status = :status, taken_at = :taken_at WHERE id = :id');
        $stmt->execute([':status' => $status, ':taken_at' => $takenAt, ':id' => $id]);
        if ($stmt->rowCount() === 0) {
            $check = po_pdo()->prepare('SELECT id FROM pro_omnibus_books WHERE id = ?');
            $check->execute([$id]);
            if (!$check->fetch()) po_json_response(404, ['error' => 'Annuncio non trovato.']);
        }
        po_json_response(200, ['ok' => true]);
    } catch (Throwable $e) {
        error_log('Pro Omnibus books PATCH failed: ' . $e->getMessage());
        po_json_response(500, ['error' => 'Non è stato possibile aggiornare lo stato del libro.']);
    }
}

header('Allow: GET, POST, PATCH');
po_json_response(405, ['error' => 'Metodo non consentito.']);
