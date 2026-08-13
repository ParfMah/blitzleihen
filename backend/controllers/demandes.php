<?php
/**
 * Gestion des demandes de crédit — remplace controllers/demandeController.js
 */

function demandes_create(): void
{
    enforce_rate_limit('demandes', 5, 60, 'Maximale Anzahl an Kreditanträgen pro Stunde erreicht.');

    $b = json_body();
    $errors = validate_demande($b);
    if (!empty($errors)) {
        respond_validation_errors($errors);
    }

    $pdo = db();
    $ref = generate_unique_reference();
    $bestehende = ($b['bestehendeVerbindlichkeiten'] ?? '') !== '' ? (float) $b['bestehendeVerbindlichkeiten'] : 0;

    $stmt = $pdo->prepare(
        'INSERT INTO demandes (
            reference_number, vorname, nachname, geburtsdatum, staatsangehoerigkeit, email, telefon,
            adresse, ort, land, beschaeftigung, einkommen, bestehende_verbindlichkeiten, kreditart,
            kreditbetrag, laufzeit, verwendungszweck, sms_verification, datenschutz, agb, schufa_zustimmung,
            statut, visiteur_ville, visiteur_region, visiteur_pays, visiteur_localisation_affichage,
            ip_adresse, user_agent
        ) VALUES (
            :ref, :vorname, :nachname, :geburtsdatum, :staatsangehoerigkeit, :email, :telefon,
            :adresse, :ort, :land, :beschaeftigung, :einkommen, :bestehende, :kreditart,
            :kreditbetrag, :laufzeit, :verwendungszweck, :sms_verification, :datenschutz, :agb, :schufa,
            "Neu", :vville, :vregion, :vpays, :vloc, :ip, :ua
        )'
    );
    $stmt->execute([
        ':ref' => $ref,
        ':vorname' => trim((string) $b['vorname']),
        ':nachname' => trim((string) $b['nachname']),
        ':geburtsdatum' => substr((string) $b['geburtsdatum'], 0, 10),
        ':staatsangehoerigkeit' => $b['staatsangehoerigkeit'] ?? 'deutsch',
        ':email' => strtolower(trim((string) $b['email'])),
        ':telefon' => trim((string) $b['telefon']),
        ':adresse' => trim((string) $b['adresse']),
        ':ort' => trim((string) $b['ort']),
        ':land' => trim((string) $b['land']),
        ':beschaeftigung' => $b['beschaeftigung'],
        ':einkommen' => (float) $b['einkommen'],
        ':bestehende' => $bestehende,
        ':kreditart' => $b['kreditart'],
        ':kreditbetrag' => (float) $b['kreditbetrag'],
        ':laufzeit' => (int) $b['laufzeit'],
        ':verwendungszweck' => trim((string) ($b['verwendungszweck'] ?? '')),
        ':sms_verification' => ($b['sms_verification'] ?? 'nein') === 'ja' ? 'ja' : 'nein',
        ':datenschutz' => is_truthy_checkbox($b['datenschutz'] ?? null) ? 1 : 0,
        ':agb' => is_truthy_checkbox($b['agb'] ?? null) ? 1 : 0,
        ':schufa' => is_truthy_checkbox($b['schufa_zustimmung'] ?? null) ? 1 : 0,
        ':vville' => $b['visiteurVille'] ?? '',
        ':vregion' => $b['visiteurRegion'] ?? '',
        ':vpays' => $b['visiteurPays'] ?? '',
        ':vloc' => $b['visiteurLocalisationAffichage'] ?? '',
        ':ip' => client_ip(),
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);
    $id = (int) $pdo->lastInsertId();

    $histStmt = $pdo->prepare(
        'INSERT INTO demande_statut_historique (demande_id, statut, commentaire) VALUES (:id, "Neu", "Antrag eingegangen")'
    );
    $histStmt->execute([':id' => $id]);

    error_log("📋 Nouvelle demande : {$ref} | {$b['kreditart']} {$b['kreditbetrag']}€ | {$b['email']}");

    // Envoi des emails (best-effort, ne bloque jamais la réponse au client)
    $row = fetch_demande_row($id);
    try {
        $mail = email_confirmation_client($row);
        // Reply-To kredit@ : le client peut répondre à cet email pour envoyer ses pièces justificatives.
        $r1 = send_mail($row['email'], $mail['subject'], $mail['html'], KREDIT_EMAIL);
        if ($r1['success']) {
            $pdo->prepare('UPDATE demandes SET email_client_envoye = 1 WHERE id = :id')->execute([':id' => $id]);
        } else {
            error_log("✉️  Échec email client ({$row['email']}) pour {$ref} : " . ($r1['error'] ?? 'raison inconnue'));
        }
        if (KREDIT_EMAIL !== '') {
            $mail2 = email_notification_conseiller($row);
            $r2 = send_mail(KREDIT_EMAIL, $mail2['subject'], $mail2['html']);
            if ($r2['success']) {
                $pdo->prepare('UPDATE demandes SET email_conseiller_envoye = 1 WHERE id = :id')->execute([':id' => $id]);
            } else {
                error_log("✉️  Échec email kredit@ (" . KREDIT_EMAIL . ") pour {$ref} : " . ($r2['error'] ?? 'raison inconnue'));
            }
        }
    } catch (Throwable $e) {
        error_log('Erreur envoi email demande (non bloquant) : ' . $e->getMessage());
    }

    respond([
        'success' => true,
        'message' => 'Ihr Kreditantrag wurde erfolgreich eingereicht. Sie erhalten in Kürze eine Bestätigungs-E-Mail.',
        'referenceNumber' => $ref,
        'demande' => [
            'id' => (string) $id,
            'referenceNumber' => $ref,
            'kreditart' => $b['kreditart'],
            'kreditbetrag' => (float) $b['kreditbetrag'],
            'laufzeit' => (int) $b['laufzeit'],
            'statut' => 'Neu',
            'createdAt' => date(DATE_ATOM),
        ],
    ], 201);
}

function demandes_abandon(): void
{
    $b = json_body();
    $email = trim((string) ($b['email'] ?? ''));
    if ($email === '' || strpos($email, '@') === false) {
        respond_ok([], 'Email manquant — abandon ignoré');
    }
    $labels = [
        1 => 'Schritt 1 von 3 (Persönliche Daten)',
        2 => 'Schritt 2 von 3 (Kreditangaben)',
        3 => 'Schritt 3 von 3 (Bestätigung, nicht abgeschickt)',
    ];
    $etape = (int) ($b['etape'] ?? 0);
    $etapeLabel = $labels[$etape] ?? ("Schritt {$etape}");
    $loc = $b['visiteurLocalisationAffichage'] ?? '';
    error_log("⚠️  Abandon formulaire : {$email} | {$etapeLabel} | {$loc}");
    respond_ok([], 'Abandon enregistré');
}

function demandes_list(): void
{
    $admin = require_auth();
    [$page, $limit, $skip] = paginate_params();
    $pdo = db();

    $where = [];
    $params = [];

    $statutsOk = ['Neu', 'Analyse', 'Akzeptiert', 'Abgelehnt'];
    if (!empty($_GET['statut']) && in_array($_GET['statut'], $statutsOk, true)) {
        $where[] = 'statut = :statut';
        $params[':statut'] = $_GET['statut'];
    }
    if (!empty($_GET['kreditart'])) {
        $where[] = 'kreditart = :kreditart';
        $params[':kreditart'] = $_GET['kreditart'];
    }
    if (!empty($_GET['search'])) {
        $where[] = '(vorname LIKE :s1 OR nachname LIKE :s2 OR email LIKE :s3 OR reference_number LIKE :s4)';
        $like = '%' . $_GET['search'] . '%';
        $params[':s1'] = $like; $params[':s2'] = $like; $params[':s3'] = $like; $params[':s4'] = $like;
    }
    if (!empty($_GET['depuis'])) {
        $where[] = 'created_at >= :depuis';
        $params[':depuis'] = $_GET['depuis'];
    }
    if (!empty($_GET['jusqu'])) {
        $where[] = 'created_at <= :jusqu';
        $params[':jusqu'] = $_GET['jusqu'];
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sortMap = ['recent' => 'created_at DESC', 'ancien' => 'created_at ASC', 'montant' => 'kreditbetrag DESC'];
    $orderBy = $sortMap[$_GET['sort'] ?? ''] ?? 'created_at DESC';

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS n FROM demandes {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()['n'];

    $stmt = $pdo->prepare("SELECT * FROM demandes {$whereSql} ORDER BY {$orderBy} LIMIT :limit OFFSET :skip");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':skip', $skip, PDO::PARAM_INT);
    $stmt->execute();
    $rows = array_map('demande_to_api', $stmt->fetchAll());

    respond_ok([
        'data' => [
            'demandes' => $rows,
            'pagination' => [
                'total' => $total, 'page' => $page, 'limit' => $limit,
                'pages' => (int) ceil($total / $limit),
                'hasNext' => $page < ceil($total / $limit),
                'hasPrev' => $page > 1,
            ],
        ],
    ]);
}

function fetch_demande_row(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM demandes WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function demandes_get(array $params): void
{
    require_auth();
    $id = (int) $params['id'];
    $row = fetch_demande_row($id);
    if (!$row) {
        respond_error("Antrag nicht gefunden (ID: {$id})", 404);
    }

    $histStmt = db()->prepare(
        'SELECT h.*, a.name AS modifie_par_nom, a.email AS modifie_par_email
         FROM demande_statut_historique h LEFT JOIN admins a ON a.id = h.modifie_par
         WHERE h.demande_id = :id ORDER BY h.date ASC'
    );
    $histStmt->execute([':id' => $id]);
    $historique = $histStmt->fetchAll();

    $noteStmt = db()->prepare(
        'SELECT n.*, a.name AS auteur_nom, a.email AS auteur_email
         FROM demande_notes n LEFT JOIN admins a ON a.id = n.auteur_admin_id
         WHERE n.demande_id = :id ORDER BY n.created_at DESC LIMIT 20'
    );
    $noteStmt->execute([':id' => $id]);
    $notes = $noteStmt->fetchAll();

    $demande = demande_to_api($row);
    $demande['historiqueStatuts'] = array_map(fn($h) => [
        'statut' => $h['statut'], 'date' => $h['date'],
        'commentaire' => $h['commentaire'],
        'modifiePar' => $h['modifie_par'] ? ['name' => $h['modifie_par_nom'], 'email' => $h['modifie_par_email']] : null,
    ], $historique);

    $messages = array_map(fn($n) => [
        'id' => (string) $n['id'], 'type' => 'note_interne', 'corps' => $n['texte'],
        'createdAt' => $n['created_at'],
        'auteur' => $n['auteur_admin_id'] ? ['name' => $n['auteur_nom'], 'email' => $n['auteur_email']] : null,
    ], $notes);

    respond_ok(['data' => ['demande' => $demande, 'messages' => $messages]]);
}

function demandes_update_statut(array $params): void
{
    $admin = require_auth();
    $id = (int) $params['id'];
    $b = json_body();
    $statutsOk = ['Neu', 'Analyse', 'Akzeptiert', 'Abgelehnt'];
    if (empty($b['statut']) || !in_array($b['statut'], $statutsOk, true)) {
        respond_validation_errors(['statut' => 'Ungültiger Status — erlaubt: Neu, Analyse, Akzeptiert, Abgelehnt']);
    }

    $row = fetch_demande_row($id);
    if (!$row) {
        respond_error('Antrag nicht gefunden', 404);
    }

    $ancien = $row['statut'];
    if ($ancien === $b['statut']) {
        respond_ok(['data' => ['demande' => demande_to_api($row)]], 'Status ist bereits aktuell — keine Änderung vorgenommen');
    }

    $pdo = db();
    $pdo->prepare('UPDATE demandes SET statut = :s WHERE id = :id')->execute([':s' => $b['statut'], ':id' => $id]);
    $pdo->prepare(
        'INSERT INTO demande_statut_historique (demande_id, statut, modifie_par, commentaire) VALUES (:id, :s, :admin, :c)'
    )->execute([':id' => $id, ':s' => $b['statut'], ':admin' => $admin['id'], ':c' => $b['commentaire'] ?? '']);

    error_log("📊 Statut modifié : {$row['reference_number']} | {$ancien} → {$b['statut']} | par {$admin['email']}");

    $updated = fetch_demande_row($id);
    try {
        $mail = email_changement_statut($updated);
        // Reply-To hilfe@ : assistance pour un dossier déjà en cours de traitement.
        send_mail($updated['email'], $mail['subject'], $mail['html'], HILFE_EMAIL);
    } catch (Throwable $e) {
        error_log('Erreur envoi notification statut : ' . $e->getMessage());
    }

    respond_ok([
        'data' => ['demande' => [
            'id' => (string) $id, 'referenceNumber' => $row['reference_number'],
            'statut' => $b['statut'], 'updatedAt' => date(DATE_ATOM),
        ]],
    ], "Status erfolgreich geändert: {$ancien} → {$b['statut']}");
}

function demandes_assign(array $params): void
{
    $admin = require_auth();
    $id = (int) $params['id'];
    $b = json_body();
    $adminId = !empty($b['adminId']) ? (int) $b['adminId'] : (int) $admin['id'];

    $stmt = db()->prepare('UPDATE demandes SET assigne_a = :a WHERE id = :id');
    $stmt->execute([':a' => $adminId, ':id' => $id]);

    $row = fetch_demande_row($id);
    if (!$row) {
        respond_error('Antrag nicht gefunden', 404);
    }
    respond_ok(['data' => ['demande' => demande_to_api($row)]], 'Antrag erfolgreich zugewiesen');
}

function demandes_add_note(array $params): void
{
    $admin = require_auth();
    $id = (int) $params['id'];
    $b = json_body();
    $texte = trim((string) ($b['texte'] ?? ''));
    if ($texte === '') {
        respond_error('Notiztext ist erforderlich', 400);
    }

    $row = fetch_demande_row($id);
    if (!$row) {
        respond_error('Antrag nicht gefunden', 404);
    }

    $pdo = db();
    $pdo->prepare('UPDATE demandes SET note_interne = :t WHERE id = :id')->execute([':t' => $texte, ':id' => $id]);
    $stmt = $pdo->prepare('INSERT INTO demande_notes (demande_id, texte, auteur_admin_id) VALUES (:id, :t, :a)');
    $stmt->execute([':id' => $id, ':t' => $texte, ':a' => $admin['id']]);

    respond_ok([
        'data' => ['message' => [
            'id' => (string) $pdo->lastInsertId(), 'corps' => $texte, 'type' => 'note_interne',
            'createdAt' => date(DATE_ATOM),
        ]],
    ], 'Notiz erfolgreich hinzugefügt', 201);
}

function demandes_delete(array $params): void
{
    $admin = require_auth();
    require_role($admin, ['superadmin']);
    $id = (int) $params['id'];
    $row = fetch_demande_row($id);
    if (!$row) {
        respond_error('Antrag nicht gefunden', 404);
    }
    db()->prepare('DELETE FROM demandes WHERE id = :id')->execute([':id' => $id]);
    error_log("🗑️  Demande supprimée : {$row['reference_number']} | par {$admin['email']}");
    respond_ok([], "Antrag {$row['reference_number']} wurde endgültig gelöscht");
}
