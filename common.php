<?php
// Funzioni condivise delle API PHP per Tophost.

function po_json_response(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function po_start_session(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    session_name('pro_omnibus_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function po_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 1300000) {
        po_json_response(413, ['error' => 'La richiesta è troppo grande.']);
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        po_json_response(400, ['error' => 'Richiesta non valida.']);
    }
    return $decoded;
}

function po_config(): array {
    $path = dirname(__DIR__) . '/config.php';
    if (!is_file($path)) {
        po_json_response(503, ['error' => 'Configurazione del sito mancante. Completa config.php su Tophost.']);
    }
    $config = require $path;
    if (!is_array($config)) {
        po_json_response(503, ['error' => 'Configurazione del sito non valida.']);
    }
    return $config;
}

function po_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = po_config();
    foreach (['db_host', 'db_name', 'db_user', 'db_password'] as $key) {
        if (empty($c[$key]) || str_starts_with((string)$c[$key], 'INSERISCI_')) {
            po_json_response(503, ['error' => 'Database non configurato. Completa i dati MySQL in config.php.']);
        }
    }
    try {
        $dsn = 'mysql:host=' . $c['db_host'] . ';dbname=' . $c['db_name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $c['db_user'], $c['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        error_log('Pro Omnibus DB connection failed: ' . $e->getMessage());
        po_json_response(503, ['error' => 'Impossibile collegarsi al database. Controlla i dati MySQL in config.php.']);
    }
}

function po_require_admin(): void {
    po_start_session();
    if (empty($_SESSION['po_is_admin'])) {
        po_json_response(401, ['error' => 'Devi accedere come amministratore.']);
    }
}

function po_text($value, int $max): string {
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    if (function_exists('mb_strlen')) {
        if (mb_strlen($value, 'UTF-8') > $max) {
            return '';
        }
    } elseif (strlen($value) > $max * 4) {
        return '';
    }
    return $value;
}
