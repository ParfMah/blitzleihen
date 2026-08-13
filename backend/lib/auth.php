<?php
/**
 * Authentification admin par jeton opaque stocké en base (remplace le JWT).
 * Le frontend ne voit aucune différence : il reçoit toujours une chaîne
 * "token" dans la réponse de /api/auth/login et la renvoie telle quelle
 * dans l'en-tête "Authorization: Bearer <token>".
 */

function hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_password(string $plain, string $hash): bool
{
    return password_verify($plain, $hash);
}

function issue_token(int $adminId): array
{
    $token = bin2hex(random_bytes(32));
    $expiresAt = new DateTime('+' . TOKEN_LIFETIME_HOURS . ' hours');
    $stmt = db()->prepare(
        'INSERT INTO admin_tokens (admin_id, token, expires_at) VALUES (:aid, :token, :exp)'
    );
    $stmt->execute([
        ':aid' => $adminId,
        ':token' => $token,
        ':exp' => $expiresAt->format('Y-m-d H:i:s'),
    ]);
    return ['token' => $token, 'expiresAt' => $expiresAt->format(DateTime::ATOM)];
}

function public_admin(array $admin): array
{
    return [
        'id' => (string) $admin['id'],
        'name' => $admin['name'],
        'email' => $admin['email'],
        'role' => $admin['role'],
        'actif' => (bool) $admin['actif'],
        'avatar' => $admin['avatar'] ?? '',
        'derniereConnexion' => $admin['derniere_connexion'],
        'createdAt' => $admin['created_at'] ?? null,
    ];
}

/**
 * Vérifie le jeton Bearer et retourne l'admin courant, ou coupe la
 * requête avec une erreur 401/403/423 (équivalent de middleware protect()).
 */
function require_auth(): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' || stripos($header, 'Bearer ') !== 0) {
        respond_error('Nicht autorisiert — kein Token vorhanden', 401);
    }
    $token = trim(substr($header, 7));

    $stmt = db()->prepare(
        'SELECT a.* FROM admin_tokens t
         INNER JOIN admins a ON a.id = t.admin_id
         WHERE t.token = :token AND t.expires_at > NOW() LIMIT 1'
    );
    $stmt->execute([':token' => $token]);
    $admin = $stmt->fetch();

    if (!$admin) {
        respond_error('Ungültiges Token — bitte erneut anmelden', 401);
    }
    if (!(int) $admin['actif']) {
        respond_error('Ihr Konto wurde deaktiviert. Kontaktieren Sie den Support.', 403);
    }
    if (is_locked($admin)) {
        $restant = (int) ceil((strtotime($admin['verrouillage_fin']) - time()) / 60);
        respond_error("Konto gesperrt. Bitte warten Sie {$restant} Minuten.", 423);
    }
    return $admin;
}

function require_role(array $admin, array $roles): void
{
    if (!in_array($admin['role'], $roles, true)) {
        respond_error('Zugriff verweigert. Erforderliche Rolle: ' . implode(' oder ', $roles), 403);
    }
}

function is_locked(array $admin): bool
{
    if (!(int) $admin['compte_verrouille']) {
        return false;
    }
    if ($admin['verrouillage_fin'] && strtotime($admin['verrouillage_fin']) < time()) {
        $stmt = db()->prepare(
            'UPDATE admins SET compte_verrouille = 0, verrouillage_fin = NULL, login_tentatives_echouees = 0 WHERE id = :id'
        );
        $stmt->execute([':id' => $admin['id']]);
        return false;
    }
    return true;
}

function record_failed_login(array $admin): int
{
    $attempts = (int) $admin['login_tentatives_echouees'] + 1;
    if ($attempts >= 5) {
        $lockUntil = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');
        $stmt = db()->prepare(
            'UPDATE admins SET login_tentatives_echouees = :n, compte_verrouille = 1, verrouillage_fin = :fin WHERE id = :id'
        );
        $stmt->execute([':n' => $attempts, ':fin' => $lockUntil, ':id' => $admin['id']]);
    } else {
        $stmt = db()->prepare('UPDATE admins SET login_tentatives_echouees = :n WHERE id = :id');
        $stmt->execute([':n' => $attempts, ':id' => $admin['id']]);
    }
    return $attempts;
}

function reset_login_attempts(int $adminId, string $ip): void
{
    $stmt = db()->prepare(
        'UPDATE admins SET login_tentatives_echouees = 0, compte_verrouille = 0, verrouillage_fin = NULL,
         derniere_connexion = NOW(), derniere_connexion_ip = :ip WHERE id = :id'
    );
    $stmt->execute([':ip' => $ip, ':id' => $adminId]);
}
