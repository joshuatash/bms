<?php
/**
 * bms/restuarant_config.php
 * Shared database + security helpers for the restaurant module.
 */

declare(strict_types=1);

// ─── Environment loading (optional .env next to this file) ───
$envFile = __DIR__ . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        if (getenv(trim($k)) === false) {
            putenv(trim($k) . '=' . trim($v, " \t\n\r\0\x0B\"'"));
        }
    }
}

// ─── Never display errors to the browser ───
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('UTC');

// ─── Force PHP errors/fatals to surface as JSON to API callers ───
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
    }
});

// ─── Database credentials ───
define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'bms_db');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ─── Shared PDO connection ───
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_PERSISTENT         => false,
    ]);
} catch (PDOException $e) {
    error_log('[restaurant_config] DB connect failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// ═══════════════════════════════════════════════════════════
// Security helpers — available to all API files below
// ═══════════════════════════════════════════════════════════

/**
 * Enforce the HTTP method. Returns 405 JSON if mismatch.
 */
function require_method(string ...$allowed): void
{
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($m, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        json_error('Method not allowed', 405);
    }
}

/**
 * Reject cross-origin mutating requests (basic CSRF protection).
 * Only applies to POST/PUT/DELETE.
 */
function enforce_same_origin(): void
{
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($m === 'GET' || $m === 'HEAD') return;

    $origin = $_SERVER['HTTP_ORIGIN']  ?? '';
    $ref    = $_SERVER['HTTP_REFERER'] ?? '';
    $host   = $_SERVER['HTTP_HOST']    ?? '';

    if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== explode(':', $host)[0]) {
        json_error('Cross-origin request blocked', 403);
    }
    if ($origin === '' && $ref !== '') {
        if (parse_url($ref, PHP_URL_HOST) !== explode(':', $host)[0]) {
            json_error('Cross-origin request blocked', 403);
        }
    }
}

/**
 * Read JSON body OR fall back to form POST. Rejects non-object payloads.
 */
function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return $_POST;

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        json_error('Invalid JSON body', 400);
    }
    return $decoded;
}

/**
 * Emit a JSON error and stop.
 */
function json_error(string $msg, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

/**
 * Emit a JSON success payload.
 */
function json_ok(array $payload): void
{
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success'] + $payload, JSON_UNESCAPED_UNICODE);
}

/**
 * Sanitize a string field: trim, strip control chars, enforce max length.
 */
function clean_str($val, int $max = 200): string
{
    if (!is_string($val)) return '';
    $val = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($val));
    return mb_substr($val, 0, $max);
}

/**
 * Parse a positive int field or return null.
 */
function pos_int($val): ?int
{
    if (!is_numeric($val)) return null;
    $i = (int)$val;
    return $i > 0 ? $i : null;
}

/**
 * Parse a non-negative decimal field or return null.
 */
function nonneg_float($val): ?float
{
    if (!is_numeric($val)) return null;
    $f = (float)$val;
    return $f >= 0 ? round($f, 2) : null;
}

/**
 * Whitelisted enum check.
 */
function one_of($val, array $allowed, $default)
{
    return in_array($val, $allowed, true) ? $val : $default;
}