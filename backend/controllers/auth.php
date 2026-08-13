<?php
/**
 * Authentification admin — remplace controllers/authController.js
 */

function auth_login(): void
{
    $b = json_body();
    $errors = validate_login($b);
    if (!empty($errors)) {
        respond_validation_errors($errors);
    }

    $ip = client_ip();
    $username = strtolower(trim((string) $b['username']));

    $stmt = db()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        respond_error('E-Mail oder Passwort ist falsch', 401);
    }
    if (!(int) $admin['actif']) {
        respond_error('Ihr Konto wurde deaktiviert. Kontaktieren Sie den Support.', 403);
    }
    if (is_locked($admin)) {
        $restant = (int) ceil((strtotime($admin['verrouillage_fin']) - time()) / 60);
        respond_error("Konto gesperrt nach zu vielen Fehlversuchen. Bitte warten Sie {$restant} Minuten.", 423);
    }

    if (!verify_password((string) $b['password'], $admin['password_hash'])) {
        $attempts = record_failed_login($admin);
        $remaining = max(0, 5 - $attempts);
        $suffix = $remaining > 0 ? "Noch {$remaining} Versuch(e) verbleibend." : 'Konto wird gesperrt.';
        respond_error("E-Mail oder Passwort ist falsch. {$suffix}", 401);
    }

    reset_login_attempts((int) $admin['id'], $ip);
    $issued = issue_token((int) $admin['id']);

    error_log("✅ Admin connecté : {$admin['email']} | IP : {$ip}");

    $admin['derniere_connexion'] = date('Y-m-d H:i:s');
    respond_ok([
        'token' => $issued['token'],
        'expiresAt' => $issued['expiresAt'],
        'user' => public_admin($admin),
    ], 'Erfolgreich angemeldet');
}

function auth_logout(): void
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if (stripos($header, 'Bearer ') === 0) {
        $token = trim(substr($header, 7));
        db()->prepare('DELETE FROM admin_tokens WHERE token = :t')->execute([':t' => $token]);
    }
    respond_ok([], 'Erfolgreich abgemeldet. Bitte löschen Sie Ihren Token.');
}

function auth_me(): void
{
    $admin = require_auth();
    respond_ok(['user' => public_admin($admin)]);
}

function auth_refresh(): void
{
    $admin = require_auth();
    $issued = issue_token((int) $admin['id']);
    respond_ok(['token' => $issued['token'], 'expiresAt' => $issued['expiresAt']]);
}
