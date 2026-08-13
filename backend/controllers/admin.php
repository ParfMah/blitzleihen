<?php
/**
 * Tableau de bord et gestion des administrateurs — remplace controllers/adminController.js
 */

function admin_stats(): void
{
    require_auth();
    $pdo = db();

    $total = (int) $pdo->query('SELECT COUNT(*) AS n FROM demandes')->fetch()['n'];

    $statutMap = ['Neu' => 0, 'Analyse' => 0, 'Akzeptiert' => 0, 'Abgelehnt' => 0];
    foreach ($pdo->query('SELECT statut, COUNT(*) AS n FROM demandes GROUP BY statut') as $r) {
        $statutMap[$r['statut']] = (int) $r['n'];
    }

    $montants = $pdo->query('SELECT COALESCE(SUM(kreditbetrag),0) AS total, COALESCE(AVG(kreditbetrag),0) AS moyen FROM demandes')->fetch();

    $parKreditart = [];
    foreach ($pdo->query('SELECT kreditart, COUNT(*) AS n, AVG(kreditbetrag) AS moyen FROM demandes GROUP BY kreditart ORDER BY n DESC') as $r) {
        $parKreditart[] = ['type' => $r['kreditart'], 'count' => (int) $r['n'], 'montantMoyen' => (int) round($r['moyen'])];
    }

    $recentes = array_map('demande_to_api', $pdo->query(
        'SELECT * FROM demandes ORDER BY created_at DESC LIMIT 5'
    )->fetchAll());

    $aujourdhui = (int) $pdo->query(
        'SELECT COUNT(*) AS n FROM demandes WHERE created_at >= CURDATE()'
    )->fetch()['n'];

    $activite = [];
    $stmt = $pdo->query(
        "SELECT DATE(created_at) AS jour, COUNT(*) AS n, COALESCE(SUM(kreditbetrag),0) AS montant
         FROM demandes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY DATE(created_at) ORDER BY jour ASC"
    );
    foreach ($stmt as $r) {
        $activite[] = ['date' => $r['jour'], 'count' => (int) $r['n'], 'montant' => (int) round($r['montant'])];
    }

    respond_ok(['data' => [
        'total' => $total,
        'parStatut' => $statutMap,
        'aujourdhui' => $aujourdhui,
        'tauxAcceptation' => $total > 0 ? (int) round($statutMap['Akzeptiert'] / $total * 100) : 0,
        'montants' => ['total' => (int) round($montants['total']), 'moyen' => (int) round($montants['moyen'])],
        'parKreditart' => $parKreditart,
        'demandesRecentes' => $recentes,
        'activite7Jours' => $activite,
    ]]);
}

function admin_stats_periode(): void
{
    require_auth();
    $depuis = !empty($_GET['depuis']) ? $_GET['depuis'] : date('Y-m-d', strtotime('-30 days'));
    $jusqu = !empty($_GET['jusqu']) ? $_GET['jusqu'] : date('Y-m-d');

    $stmt = db()->prepare(
        "SELECT DATE(created_at) AS jour, COUNT(*) AS n, COALESCE(SUM(kreditbetrag),0) AS montant,
                SUM(CASE WHEN statut = 'Akzeptiert' THEN 1 ELSE 0 END) AS acceptes
         FROM demandes WHERE created_at BETWEEN :depuis AND :jusqu
         GROUP BY DATE(created_at) ORDER BY jour ASC"
    );
    $stmt->execute([':depuis' => $depuis, ':jusqu' => $jusqu]);
    $activite = array_map(fn($r) => [
        '_id' => $r['jour'], 'count' => (int) $r['n'],
        'montant' => (int) round($r['montant']), 'acceptes' => (int) $r['acceptes'],
    ], $stmt->fetchAll());

    respond_ok(['data' => ['periode' => ['depuis' => $depuis, 'jusqu' => $jusqu], 'activite' => $activite]]);
}

function admin_list_admins(): void
{
    $admin = require_auth();
    require_role($admin, ['superadmin']);
    $rows = db()->query('SELECT * FROM admins ORDER BY created_at DESC')->fetchAll();
    respond_ok(['data' => ['admins' => array_map('public_admin', $rows), 'total' => count($rows)]]);
}

function admin_create_admin(): void
{
    $current = require_auth();
    require_role($current, ['superadmin']);
    $b = json_body();

    $name = trim((string) ($b['name'] ?? ''));
    $email = strtolower(trim((string) ($b['email'] ?? '')));
    $password = (string) ($b['password'] ?? '');
    $role = in_array($b['role'] ?? '', ['admin', 'superadmin', 'conseiller'], true) ? $b['role'] : 'conseiller';

    if ($name === '') respond_validation_errors(['name' => 'Name ist erforderlich']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond_validation_errors(['email' => 'Ungültige E-Mail']);
    if (mb_strlen($password) < 8) respond_validation_errors(['password' => 'Passwort: min. 8 Zeichen']);

    $exists = db()->prepare('SELECT id FROM admins WHERE email = :e');
    $exists->execute([':e' => $email]);
    if ($exists->fetch()) {
        respond_error('Diese E-Mail-Adresse ist bereits vergeben', 409);
    }

    $stmt = db()->prepare('INSERT INTO admins (name, email, password_hash, role, actif) VALUES (:n, :e, :p, :r, 1)');
    $stmt->execute([':n' => $name, ':e' => $email, ':p' => hash_password($password), ':r' => $role]);
    $id = (int) db()->lastInsertId();

    error_log("👤 Nouvel admin créé : {$email} ({$role}) par {$current['email']}");
    respond_ok(['data' => ['admin' => ['id' => (string) $id, 'name' => $name, 'email' => $email, 'role' => $role]]], 'Administrator erfolgreich erstellt', 201);
}

function admin_update_admin(array $params): void
{
    $current = require_auth();
    require_role($current, ['superadmin']);
    $id = (int) $params['id'];
    $b = json_body();

    if ($id === (int) $current['id'] && array_key_exists('actif', $b) && $b['actif'] === false) {
        respond_error('Sie können Ihr eigenes Konto nicht deaktivieren', 400);
    }

    $fields = [];
    $values = [':id' => $id];
    foreach (['name' => 'name', 'email' => 'email', 'role' => 'role', 'actif' => 'actif', 'avatar' => 'avatar'] as $key => $col) {
        if (array_key_exists($key, $b)) {
            $val = $b[$key];
            if ($key === 'email') $val = strtolower((string) $val);
            if ($key === 'actif') $val = $val ? 1 : 0;
            $fields[] = "{$col} = :{$key}";
            $values[":{$key}"] = $val;
        }
    }
    if (empty($fields)) {
        respond_error('Keine Änderungen übermittelt', 400);
    }
    $stmt = db()->prepare('UPDATE admins SET ' . implode(', ', $fields) . ' WHERE id = :id');
    $stmt->execute($values);

    $row = db()->prepare('SELECT * FROM admins WHERE id = :id');
    $row->execute([':id' => $id]);
    $admin = $row->fetch();
    if (!$admin) respond_error('Administrator nicht gefunden', 404);

    respond_ok(['data' => ['admin' => public_admin($admin)]], 'Administrator aktualisiert');
}

function admin_change_password(): void
{
    $admin = require_auth();
    $b = json_body();
    $ancien = (string) ($b['ancienPassword'] ?? '');
    $nouveau = (string) ($b['nouveauPassword'] ?? '');

    if ($ancien === '' || $nouveau === '') {
        respond_error('Altes und neues Passwort sind erforderlich', 400);
    }
    if (mb_strlen($nouveau) < 8) {
        respond_error('Neues Passwort muss mindestens 8 Zeichen haben', 400);
    }
    if (!verify_password($ancien, $admin['password_hash'])) {
        respond_error('Das alte Passwort ist falsch', 401);
    }
    db()->prepare('UPDATE admins SET password_hash = :p WHERE id = :id')
        ->execute([':p' => hash_password($nouveau), ':id' => $admin['id']]);
    // On invalide toutes les sessions existantes pour forcer une reconnexion.
    db()->prepare('DELETE FROM admin_tokens WHERE admin_id = :id')->execute([':id' => $admin['id']]);

    respond_ok([], 'Passwort erfolgreich geändert. Bitte melden Sie sich erneut an.');
}

function admin_update_profile(): void
{
    $admin = require_auth();
    $b = json_body();
    $fields = [];
    $values = [':id' => $admin['id']];
    if (array_key_exists('name', $b)) { $fields[] = 'name = :name'; $values[':name'] = $b['name']; }
    if (array_key_exists('avatar', $b)) { $fields[] = 'avatar = :avatar'; $values[':avatar'] = $b['avatar']; }
    if (!empty($fields)) {
        db()->prepare('UPDATE admins SET ' . implode(', ', $fields) . ' WHERE id = :id')->execute($values);
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = :id');
    $stmt->execute([':id' => $admin['id']]);
    respond_ok(['data' => ['admin' => public_admin($stmt->fetch())]], 'Profil aktualisiert');
}

function admin_send_email(): void
{
    require_auth();
    $b = json_body();
    $destinataire = trim((string) ($b['destinataire'] ?? ''));
    $sujet = trim((string) ($b['sujet'] ?? ''));
    $message = trim((string) ($b['message'] ?? ''));

    if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) respond_validation_errors(['destinataire' => 'Ungültige E-Mail-Adresse']);
    if ($sujet === '') respond_validation_errors(['sujet' => 'Betreff ist erforderlich']);
    if ($message === '') respond_validation_errors(['message' => 'Nachricht ist erforderlich']);

    $mail = email_libre($message, $sujet);
    $result = send_mail($destinataire, $mail['subject'], $mail['html']);
    if (!$result['success']) {
        respond_error('E-Mail konnte nicht gesendet werden: ' . ($result['error'] ?? 'unbekannter Fehler'), 502);
    }
    respond_ok([], 'E-Mail erfolgreich an ' . $destinataire . ' gesendet.');
}

/**
 * Diagnostic — teste la connexion SMTP en conditions réelles et renvoie
 * l'erreur exacte le cas échéant, sans avoir à fouiller les logs serveur.
 * Protégé par authentification admin (pas d'abus possible depuis l'extérieur).
 */
function admin_test_smtp(): void
{
    $admin = require_auth();
    $cible = filter_var($admin['email'], FILTER_VALIDATE_EMAIL) ? $admin['email'] : KONTAKT_EMAIL;
    if ($cible === '') {
        respond_error('Keine gültige Test-Zieladresse verfügbar (weder Admin-E-Mail noch KONTAKT_EMAIL gesetzt)', 400);
    }
    if (SMTP_HOST === '' || SMTP_USER === '' || SMTP_PASS === '') {
        respond_error('SMTP nicht konfiguriert (SMTP_HOST/SMTP_USER/SMTP_PASS fehlen in secrets.php)', 500);
    }
    $mail = email_libre(
        "Dies ist eine Testnachricht, ausgelöst über den Diagnose-Endpunkt.\nWenn Sie diese E-Mail erhalten, funktioniert der SMTP-Versand korrekt.",
        '✅ SMTP-Test — Blitz Leihen'
    );
    $result = send_mail($cible, $mail['subject'], $mail['html']);
    if (!$result['success']) {
        respond([
            'success' => false,
            'message' => 'SMTP-Test fehlgeschlagen',
            'error' => $result['error'] ?? 'unbekannter Fehler',
            'smtpHost' => SMTP_HOST, 'smtpPort' => (int) SMTP_PORT, 'smtpSecure' => (bool) SMTP_SECURE,
            'smtpUser' => SMTP_USER,
        ], 502);
    }
    respond_ok(['smtpHost' => SMTP_HOST, 'smtpPort' => (int) SMTP_PORT], "SMTP-Test erfolgreich — E-Mail an {$cible} gesendet.");
}
