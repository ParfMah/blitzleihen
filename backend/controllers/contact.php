<?php
/**
 * Gestion des messages de contact — remplace controllers/contactController.js
 */

function fetch_contact_row(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM contacts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function contact_create(): void
{
    enforce_rate_limit('contact', 10, 60, 'Maximale Anzahl an Nachrichten pro Stunde erreicht.');

    $b = json_body();
    $errors = validate_contact($b);
    if (!empty($errors)) {
        respond_validation_errors($errors);
    }

    $data = [
        'name' => $b['name'] ?? ($b['nom'] ?? ($b['vorname'] ?? '—')),
        'email' => $b['email'] ?? '—',
        'telefon' => $b['telefon'] ?? ($b['phone'] ?? '—'),
        'betreff' => $b['betreff'] ?? ($b['sujet'] ?? ($b['subject'] ?? '—')),
        'nachricht' => $b['nachricht'] ?? ($b['message'] ?? '—'),
    ];

    error_log("✉️  Nouveau message de contact : {$data['name']} <{$data['email']}>");

    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO contacts (nom, email, telefon, betreff, nachricht, datenschutz_accepte, visiteur_localisation_affichage, ip_adresse, user_agent)
         VALUES (:nom, :email, :telefon, :betreff, :nachricht, :dsg, :loc, :ip, :ua)'
    );
    $stmt->execute([
        ':nom' => $data['name'], ':email' => $data['email'], ':telefon' => $data['telefon'],
        ':betreff' => $data['betreff'], ':nachricht' => $data['nachricht'],
        ':dsg' => is_truthy_checkbox($b['datenschutz'] ?? null) ? 1 : 0,
        ':loc' => $b['visiteurLocalisationAffichage'] ?? '',
        ':ip' => client_ip(), ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);
    $contactId = (int) $pdo->lastInsertId();

    if (KONTAKT_EMAIL !== '') {
        $mail = email_message_contact($data);
        $result = send_mail(KONTAKT_EMAIL, $mail['subject'], $mail['html']);
        if (!$result['success']) {
            respond_error('Ihre Nachricht konnte nicht zugestellt werden. Bitte versuchen Sie es später erneut oder rufen Sie uns an.', 502);
        }
    }

    try {
        $confirm = email_confirmation_contact($data);
        send_mail($data['email'], $confirm['subject'], $confirm['html'], KONTAKT_EMAIL);
    } catch (Throwable $e) {
        error_log('Erreur envoi accusé de réception contact (non bloquant) : ' . $e->getMessage());
    }

    respond_ok(
        ['contactId' => $contactId],
        'Ihre Nachricht wurde erfolgreich gesendet. Wir melden uns innerhalb von 24 Stunden bei Ihnen.'
    );
}

function contact_list(): void
{
    require_auth();
    [$page, $limit, $skip] = paginate_params();
    $pdo = db();

    $where = [];
    $params = [];
    $statutsOk = ['neu', 'beantwortet', 'geschlossen'];
    if (!empty($_GET['statut']) && in_array($_GET['statut'], $statutsOk, true)) {
        $where[] = 'statut = :statut';
        $params[':statut'] = $_GET['statut'];
    }
    if (!empty($_GET['search'])) {
        $where[] = '(nom LIKE :s1 OR email LIKE :s2 OR betreff LIKE :s3 OR nachricht LIKE :s4)';
        $like = '%' . $_GET['search'] . '%';
        $params[':s1'] = $like; $params[':s2'] = $like; $params[':s3'] = $like; $params[':s4'] = $like;
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS n FROM contacts {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()['n'];

    $stmt = $pdo->prepare("SELECT * FROM contacts {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :skip");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':skip', $skip, PDO::PARAM_INT);
    $stmt->execute();
    $rows = array_map('contact_to_api', $stmt->fetchAll());

    respond_ok([
        'data' => [
            'contacts' => $rows,
            'pagination' => [
                'total' => $total, 'page' => $page, 'limit' => $limit,
                'pages' => (int) ceil($total / $limit),
                'hasNext' => $page < ceil($total / $limit), 'hasPrev' => $page > 1,
            ],
        ],
    ]);
}

function contact_get(array $params): void
{
    require_auth();
    $id = (int) $params['id'];
    $row = fetch_contact_row($id);
    if (!$row) {
        respond_error("Nachricht nicht gefunden (ID: {$id})", 404);
    }
    $stmt = db()->prepare(
        'SELECT r.*, a.name AS auteur_email_nom FROM contact_reponses r
         LEFT JOIN admins a ON a.id = r.auteur_admin_id WHERE r.contact_id = :id ORDER BY r.created_at ASC'
    );
    $stmt->execute([':id' => $id]);
    $reponses = array_map(fn($r) => [
        'texte' => $r['texte'], 'auteurNom' => $r['auteur_nom'],
        'envoyeParEmail' => (bool) $r['envoye_par_email'], 'erreurEnvoi' => $r['erreur_envoi'],
        'createdAt' => $r['created_at'],
    ], $stmt->fetchAll());

    $contact = contact_to_api($row);
    $contact['reponses'] = $reponses;
    respond_ok(['data' => ['contact' => $contact]]);
}

function contact_reply(array $params): void
{
    $admin = require_auth();
    $id = (int) $params['id'];
    $b = json_body();
    $texte = trim((string) ($b['texte'] ?? ''));
    if ($texte === '') {
        respond_error('Antworttext ist erforderlich', 400);
    }
    $row = fetch_contact_row($id);
    if (!$row) {
        respond_error('Nachricht nicht gefunden', 404);
    }

    $sujetDefaut = $row['betreff'] !== '—' ? $row['betreff'] : 'Ihre Anfrage bei Blitz Leihen';
    $sujet = !empty($b['sujet']) ? trim($b['sujet']) : ('Re: ' . $sujetDefaut);

    $mail = email_reponse_contact($texte, $sujet);
    $result = send_mail($row['email'], $mail['subject'], $mail['html'], KONTAKT_EMAIL);

    $pdo = db();
    $pdo->prepare(
        'INSERT INTO contact_reponses (contact_id, texte, auteur_admin_id, auteur_nom, envoye_par_email, erreur_envoi)
         VALUES (:cid, :texte, :admin, :nom, :ok, :err)'
    )->execute([
        ':cid' => $id, ':texte' => $texte, ':admin' => $admin['id'], ':nom' => $admin['name'],
        ':ok' => $result['success'] ? 1 : 0, ':err' => $result['success'] ? '' : ($result['error'] ?? 'Unbekannter Fehler'),
    ]);
    $pdo->prepare('UPDATE contacts SET statut = "beantwortet" WHERE id = :id')->execute([':id' => $id]);

    $updated = fetch_contact_row($id);
    if (!$result['success']) {
        respond([
            'success' => false,
            'message' => 'Antwort wurde gespeichert, aber der Versand per E-Mail ist fehlgeschlagen: ' . ($result['error'] ?? 'unbekannter Fehler'),
            'data' => ['contact' => contact_to_api($updated)],
        ], 502);
    }
    respond_ok(['data' => ['contact' => contact_to_api($updated)]], 'Antwort erfolgreich an ' . $row['email'] . ' gesendet.');
}

function contact_update_statut(array $params): void
{
    require_auth();
    $id = (int) $params['id'];
    $b = json_body();
    $statutsOk = ['neu', 'beantwortet', 'geschlossen'];
    if (empty($b['statut']) || !in_array($b['statut'], $statutsOk, true)) {
        respond_error('Ungültiger Status', 400);
    }
    $row = fetch_contact_row($id);
    if (!$row) {
        respond_error('Nachricht nicht gefunden', 404);
    }
    db()->prepare('UPDATE contacts SET statut = :s WHERE id = :id')->execute([':s' => $b['statut'], ':id' => $id]);
    respond_ok(['data' => ['contact' => contact_to_api(fetch_contact_row($id))]], 'Status erfolgreich geändert');
}
