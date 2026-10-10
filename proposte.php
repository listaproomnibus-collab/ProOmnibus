<?php
require_once __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    po_json_response(405, ['error' => 'Metodo non consentito.']);
}

po_start_session();
$payload = po_input();

// Campo-esca anti-bot: se compilato, rispondi senza inoltrare nulla.
if (!empty($payload['website'])) {
    po_json_response(200, ['ok' => true]);
}

$areas = [
    'strutture' => 'Strutture e distributori',
    'sport' => 'Sport e attività',
    'didattica' => 'Didattica e studio',
    'eventi' => 'Eventi e socialità',
    'ambiente' => 'Ambiente e sostenibilità',
    'altro' => 'Altro',
];
$area = isset($payload['area']) && is_string($payload['area']) ? $payload['area'] : '';
$message = po_text($payload['messaggio'] ?? null, 2000);
$contact = po_text($payload['contatto'] ?? '', 100);

if (!isset($areas[$area])) {
    po_json_response(400, ['error' => 'Seleziona un’area di interesse valida.']);
}
if ($message === '') {
    po_json_response(400, ['error' => 'Scrivi la tua proposta prima di inviarla.']);
}

// Limite semplice per sessione: massimo 8 proposte ogni 10 minuti.
$now = time();
$_SESSION['po_proposal_times'] = array_values(array_filter(
    $_SESSION['po_proposal_times'] ?? [],
    static fn($time) => is_int($time) && $time > $now - 600
));
if (count($_SESSION['po_proposal_times']) >= 8) {
    po_json_response(429, ['error' => 'Hai inviato troppe richieste. Riprova tra qualche minuto.']);
}

$config = po_config();
$token = (string)($config['telegram_bot_token'] ?? '');
$chatId = (string)($config['telegram_chat_id'] ?? '');
if ($token === '' || $chatId === '' || str_starts_with($token, 'INSERISCI_') || str_starts_with($chatId, 'INSERISCI_')) {
    po_json_response(503, ['error' => 'Invio Telegram non ancora configurato. Completa config.php su Tophost.']);
}
if (!function_exists('curl_init')) {
    error_log('Pro Omnibus: estensione cURL non disponibile.');
    po_json_response(503, ['error' => 'Il servizio di invio non è disponibile al momento.']);
}

$text = "📣 Nuova proposta Pro Omnibus\n\nArea: {$areas[$area]}\nDa: " . ($contact !== '' ? $contact : 'Anonimo') . "\n\nMessaggio:\n{$message}";
$ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['chat_id' => $chatId, 'text' => $text], JSON_UNESCAPED_UNICODE),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 12,
]);
$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = is_string($responseBody) ? json_decode($responseBody, true) : null;

if ($curlError !== '' || $status < 200 || $status >= 300 || !is_array($result) || empty($result['ok'])) {
    error_log('Pro Omnibus: invio Telegram fallito. HTTP ' . $status . ($curlError !== '' ? ' / ' . $curlError : ''));
    po_json_response(502, ['error' => 'Telegram non ha accettato la proposta. Riprova più tardi.']);
}
$_SESSION['po_proposal_times'][] = $now;
po_json_response(200, ['ok' => true]);
