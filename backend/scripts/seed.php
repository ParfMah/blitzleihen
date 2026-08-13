<?php
/**
 * Crée (ou met à jour) le compte admin initial à partir de secrets.php.
 * À exécuter une seule fois depuis le terminal SSH/cPanel :
 *   php scripts/seed.php
 */

require_once __DIR__ . '/../config.php';

if (ADMIN_EMAIL === '' || ADMIN_PASSWORD === '') {
    fwrite(STDERR, "ADMIN_EMAIL / ADMIN_PASSWORD manquants dans secrets.php\n");
    exit(1);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM admins WHERE email = :e');
$stmt->execute([':e' => strtolower(ADMIN_EMAIL)]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare('UPDATE admins SET password_hash = :p, name = :n, actif = 1 WHERE id = :id')->execute([
        ':p' => hash_password(ADMIN_PASSWORD),
        ':n' => ADMIN_NAME,
        ':id' => $existing['id'],
    ]);
    echo "Compte admin existant mis à jour : " . ADMIN_EMAIL . "\n";
} else {
    $pdo->prepare(
        'INSERT INTO admins (name, email, password_hash, role, actif) VALUES (:n, :e, :p, "superadmin", 1)'
    )->execute([
        ':n' => ADMIN_NAME,
        ':e' => strtolower(ADMIN_EMAIL),
        ':p' => hash_password(ADMIN_PASSWORD),
    ]);
    echo "Compte admin créé : " . ADMIN_EMAIL . "\n";
}
echo "⚠️  Changez ce mot de passe dès la première connexion.\n";
