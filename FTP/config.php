<?php
/**
 * Public configuration. Supply values through the web server environment.
 */
$adminPassword = getenv('FILEBOX_ADMIN_PASSWORD');
$baseUrl = getenv('FILEBOX_BASE_URL');
if (!is_string($adminPassword) || strlen($adminPassword) < 12 ||
    !is_string($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL) ||
    !in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
    http_response_code(503);
    exit('Filebox configuration is incomplete. See README.md.');
}
if (!defined('ADMIN_PASSWORD_HASH')) {
    define('ADMIN_PASSWORD_HASH', password_hash($adminPassword, PASSWORD_DEFAULT));
}
unset($adminPassword);
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/data/filebox.sqlite');
}
if (!defined('UPLOADS_DIR')) {
    define('UPLOADS_DIR', __DIR__ . '/uploads');
}
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 209715200);
}
if (!defined('SYSTEM_BASE_URL')) {
    define('SYSTEM_BASE_URL', rtrim($baseUrl, '/'));
}
unset($baseUrl);

function getDbConnection() {
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    return $pdo;
}

