<?php
/**
 * Réponses JSON + CORS (équivalent du middleware CORS Express + errorHandler.js).
 */

function apply_cors(): void
{
    $allowed = array_map('trim', explode(',', FRONTEND_ORIGINS));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return $_POST ?: [];
    }
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST ?: [];
}

function respond($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function respond_ok(array $extra = [], string $message = '', int $status = 200): void
{
    respond(array_merge(['success' => true, 'message' => $message], $extra), $status);
}

function respond_error(string $message, int $status = 400, array $extra = []): void
{
    respond(array_merge(['success' => false, 'message' => $message], $extra), $status);
}

function respond_validation_errors(array $errors): void
{
    respond([
        'success' => false,
        'message' => 'Ungültige Formulardaten. Bitte überprüfen Sie Ihre Eingaben.',
        'errors' => $errors
    ], 422);
}

function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if ($ip !== '') {
                return $ip;
            }
        }
    }
    return '';
}
