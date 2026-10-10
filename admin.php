<?php
require_once __DIR__ . '/common.php';
po_start_session();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    po_json_response(200, ['authenticated' => !empty($_SESSION['po_is_admin'])]);
}
if ($method !== 'POST') {
    header('Allow: GET, POST');
    po_json_response(405, ['error' => 'Metodo non consentito.']);
}
$payload = po_input();
$action = isset($payload['action']) && is_string($payload['action']) ? $payload['action'] : 'login';

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
    po_json_response(200, ['ok' => true]);
}

if ($action !== 'login') {
    po_json_response(400, ['error' => 'Azione non riconosciuta.']);
}
$now = time();
$_SESSION['po_login_attempts'] = array_values(array_filter(
    $_SESSION['po_login_attempts'] ?? [],
    static fn($time) => is_int($time) && $time > $now - 600
));
if (count($_SESSION['po_login_attempts']) >= 8) {
    po_json_response(429, ['error' => 'Troppi tentativi di accesso. Riprova tra qualche minuto.']);
}
$_SESSION['po_login_attempts'][] = $now;

$email = isset($payload['email']) && is_string($payload['email']) ? strtolower(trim($payload['email'])) : '';
$password = isset($payload['password']) && is_string($payload['password']) ? $payload['password'] : '';
$c = po_config();
$expectedEmail = strtolower((string)($c['admin_email'] ?? ''));
$expectedPassword = (string)($c['admin_password'] ?? '');
if ($expectedEmail === '' || $expectedPassword === '' || str_contains($expectedPassword, 'INSERISCI_')) {
    po_json_response(503, ['error' => 'Credenziali amministratore non configurate in config.php.']);
}
if ($email === '' || !hash_equals($expectedEmail, $email) || $password === '' || !hash_equals($expectedPassword, $password)) {
    po_json_response(401, ['error' => 'Email o password non corrette.']);
}
session_regenerate_id(true);
$_SESSION['po_is_admin'] = true;
$_SESSION['po_login_attempts'] = [];
po_json_response(200, ['ok' => true]);
