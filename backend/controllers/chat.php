<?php
/**
 * Chat en direct — remplace controllers/chatController.js + services/chatService.js.
 * Pas de WebSocket ici : le frontend interroge régulièrement (polling) ces
 * routes toutes les 3-4 secondes pour récupérer les nouveaux messages.
 */

function conversation_to_api(array $c): array
{
    return [
        'id' => (string) $c['id'],
        '_id' => (string) $c['id'],
        'visiteurId' => $c['visiteur_id'],
        'nom' => $c['nom'],
        'email' => $c['email'],
        'visiteurLocalisationAffichage' => $c['visiteur_localisation_affichage'] ?? '',
        'statut' => $c['statut'],
        'adminAssigne' => !empty($c['admin_assigne_id']) ? [
            'id' => (string) $c['admin_assigne_id'],
            'name' => $c['admin_nom'] ?? null,
            'avatar' => $c['admin_avatar'] ?? '',
        ] : null,
        'dernierMessage' => $c['dernier_message'],
        'dernierMessageDate' => $c['dernier_message_date'],
        'dernierExpediteur' => $c['dernier_expediteur'],
        'nonLuAdmin' => (int) $c['non_lu_admin'],
        'nonLuVisiteur' => (int) $c['non_lu_visiteur'],
        'createdAt' => $c['created_at'],
        'updatedAt' => $c['updated_at'],
    ];
}

function message_to_api(array $m): array
{
    return [
        'id' => (string) $m['id'],
        '_id' => (string) $m['id'],
        'conversation' => (string) $m['conversation_id'],
        'expediteur' => $m['expediteur'],
        'auteurNom' => $m['auteur_nom'],
        'auteurAvatar' => $m['auteur_avatar'],
        'texte' => $m['texte'],
        'lu' => (bool) $m['lu'],
        'createdAt' => $m['created_at'],
    ];
}

function fetch_conversation_with_admin(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT c.*, a.name AS admin_nom, a.avatar AS admin_avatar
         FROM conversations c LEFT JOIN admins a ON a.id = c.admin_assigne_id
         WHERE c.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_or_create_conversation(string $visiteurId, array $meta = []): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE visiteur_id = :vid');
    $stmt->execute([':vid' => $visiteurId]);
    $conversation = $stmt->fetch();

    if (!$conversation) {
        $ins = $pdo->prepare(
            'INSERT INTO conversations (visiteur_id, nom, email, page_origine, ip_adresse, user_agent,
                visiteur_ville, visiteur_region, visiteur_pays, visiteur_localisation_affichage,
                dernier_message, dernier_message_date, dernier_expediteur, non_lu_visiteur)
             VALUES (:vid, :nom, :email, :page, :ip, :ua, :vville, :vregion, :vpays, :vloc, :dm, NOW(), "admin", 1)'
        );
        $texteBienvenue = 'Willkommen bei Blitz Leihen! 👋 Ein Berater kümmert sich gleich um Ihr Anliegen. Wie können wir Ihnen heute helfen?';
        $ins->execute([
            ':vid' => $visiteurId, ':nom' => $meta['nom'] ?? '', ':email' => $meta['email'] ?? '',
            ':page' => $meta['pageOrigine'] ?? '', ':ip' => $meta['ipAdresse'] ?? '', ':ua' => $meta['userAgent'] ?? '',
            ':vville' => $meta['visiteurVille'] ?? '', ':vregion' => $meta['visiteurRegion'] ?? '',
            ':vpays' => $meta['visiteurPays'] ?? '', ':vloc' => $meta['visiteurLocalisationAffichage'] ?? '',
            ':dm' => mb_substr($texteBienvenue, 0, 200),
        ]);
        $convId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'INSERT INTO chat_messages (conversation_id, expediteur, auteur_nom, texte, lu)
             VALUES (:cid, "admin", "Blitz Leihen Team", :texte, 0)'
        )->execute([':cid' => $convId, ':texte' => $texteBienvenue]);

        return fetch_conversation_with_admin($convId);
    }

    $updates = [];
    $vals = [':id' => $conversation['id']];
    if (!empty($meta['nom']) && $meta['nom'] !== $conversation['nom']) {
        $updates[] = 'nom = :nom'; $vals[':nom'] = $meta['nom'];
    }
    if (!empty($meta['email']) && $meta['email'] !== $conversation['email']) {
        $updates[] = 'email = :email'; $vals[':email'] = $meta['email'];
    }
    if (!empty($meta['visiteurLocalisationAffichage']) && $meta['visiteurLocalisationAffichage'] !== $conversation['visiteur_localisation_affichage']) {
        $updates[] = 'visiteur_ville = :vville'; $vals[':vville'] = $meta['visiteurVille'] ?? '';
        $updates[] = 'visiteur_region = :vregion'; $vals[':vregion'] = $meta['visiteurRegion'] ?? '';
        $updates[] = 'visiteur_pays = :vpays'; $vals[':vpays'] = $meta['visiteurPays'] ?? '';
        $updates[] = 'visiteur_localisation_affichage = :vloc'; $vals[':vloc'] = $meta['visiteurLocalisationAffichage'];
    }
    if ($conversation['statut'] === 'ferme') {
        $updates[] = "statut = 'ouvert'";
    }
    if (!empty($updates)) {
        $pdo->prepare('UPDATE conversations SET ' . implode(', ', $updates) . ' WHERE id = :id')->execute($vals);
    }

    return fetch_conversation_with_admin((int) $conversation['id']);
}

function get_history(int $conversationId, int $limit = 200): array
{
    $stmt = db()->prepare(
        'SELECT * FROM chat_messages WHERE conversation_id = :id ORDER BY created_at ASC, id ASC LIMIT :limit'
    );
    $stmt->bindValue(':id', $conversationId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_map('message_to_api', $stmt->fetchAll());
}

function add_chat_message(int $conversationId, string $role, string $texte, ?array $admin = null): array
{
    $texte = trim($texte);
    if ($texte === '') {
        respond_error('Nachricht darf nicht leer sein', 422);
    }
    if (mb_strlen($texte) > 4000) {
        respond_error('Nachricht zu lang (max. 4000 Zeichen)', 422);
    }

    $conversation = fetch_conversation_with_admin($conversationId);
    if (!$conversation) {
        respond_error('Konversation nicht gefunden', 404);
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO chat_messages (conversation_id, expediteur, auteur_admin_id, auteur_nom, auteur_avatar, texte, lu)
         VALUES (:cid, :role, :aid, :anom, :aavatar, :texte, 0)'
    );
    $stmt->execute([
        ':cid' => $conversationId, ':role' => $role,
        ':aid' => ($role === 'admin' && $admin) ? $admin['id'] : null,
        ':anom' => ($role === 'admin' && $admin) ? $admin['name'] : '',
        ':aavatar' => ($role === 'admin' && $admin) ? ($admin['avatar'] ?? '') : '',
        ':texte' => $texte,
    ]);
    $msgId = (int) $pdo->lastInsertId();

    $updates = [
        'dernier_message = :dm', 'dernier_message_date = NOW()', 'dernier_expediteur = :de',
    ];
    $vals = [':id' => $conversationId, ':dm' => mb_substr($texte, 0, 200), ':de' => $role];
    if ($role === 'visiteur') {
        $updates[] = 'non_lu_admin = non_lu_admin + 1';
        $updates[] = "statut = 'ouvert'";
    } else {
        $updates[] = 'non_lu_visiteur = non_lu_visiteur + 1';
        if ($admin) {
            $updates[] = 'admin_assigne_id = :aid2';
            $vals[':aid2'] = $admin['id'];
        }
    }
    $pdo->prepare('UPDATE conversations SET ' . implode(', ', $updates) . ' WHERE id = :id')->execute($vals);

    $msgStmt = $pdo->prepare('SELECT * FROM chat_messages WHERE id = :id');
    $msgStmt->execute([':id' => $msgId]);
    return message_to_api($msgStmt->fetch());
}

// --- Visiteur (public) ---

function chat_start_conversation(): void
{
    $b = json_body();
    $visiteurId = trim((string) ($b['visiteurId'] ?? ''));
    if ($visiteurId === '') {
        respond_error('visiteurId ist erforderlich', 400);
    }
    $conversation = find_or_create_conversation($visiteurId, [
        'nom' => $b['nom'] ?? '', 'email' => $b['email'] ?? '', 'pageOrigine' => $b['pageOrigine'] ?? '',
        'ipAdresse' => client_ip(), 'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'visiteurVille' => $b['visiteurVille'] ?? '', 'visiteurRegion' => $b['visiteurRegion'] ?? '',
        'visiteurPays' => $b['visiteurPays'] ?? '', 'visiteurLocalisationAffichage' => $b['visiteurLocalisationAffichage'] ?? '',
    ]);
    $messages = get_history((int) $conversation['id']);
    respond_ok(['data' => ['conversation' => conversation_to_api($conversation), 'messages' => $messages]]);
}

function chat_get_visitor_conversation(array $params): void
{
    $conversation = find_or_create_conversation($params['vid']);
    $messages = get_history((int) $conversation['id']);
    respond_ok(['data' => ['conversation' => conversation_to_api($conversation), 'messages' => $messages]]);
}

function chat_send_visitor_message(array $params): void
{
    $conversation = find_or_create_conversation($params['vid']);
    $b = json_body();
    $message = add_chat_message((int) $conversation['id'], 'visiteur', (string) ($b['texte'] ?? ''));
    respond(['success' => true, 'data' => ['message' => $message]], 201);
}

// --- Admin ---

function chat_list_conversations(): void
{
    require_auth();
    $statut = $_GET['statut'] ?? null;
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limite = max(1, (int) ($_GET['limite'] ?? 30));
    $skip = ($page - 1) * $limite;

    $where = '';
    $params = [];
    if ($statut && $statut !== 'toutes') {
        $where = 'WHERE c.statut = :statut';
        $params[':statut'] = $statut;
    }

    $countStmt = db()->prepare("SELECT COUNT(*) AS n FROM conversations c {$where}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()['n'];

    $stmt = db()->prepare(
        "SELECT c.*, a.name AS admin_nom, a.avatar AS admin_avatar FROM conversations c
         LEFT JOIN admins a ON a.id = c.admin_assigne_id {$where}
         ORDER BY c.dernier_message_date DESC LIMIT :limit OFFSET :skip"
    );
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':skip', $skip, PDO::PARAM_INT);
    $stmt->execute();
    $conversations = array_map('conversation_to_api', $stmt->fetchAll());

    respond_ok(['data' => [
        'conversations' => $conversations, 'total' => $total, 'page' => $page,
        'pages' => (int) ceil($total / $limite),
    ]]);
}

function chat_count_unread(): void
{
    require_auth();
    $total = (int) db()->query('SELECT COUNT(*) AS n FROM conversations WHERE non_lu_admin > 0')->fetch()['n'];
    respond_ok(['data' => ['total' => $total]]);
}

function chat_get_admin_conversation(array $params): void
{
    require_auth();
    $id = (int) $params['id'];
    $conversation = fetch_conversation_with_admin($id);
    if (!$conversation) respond_error('Konversation nicht gefunden', 404);
    $messages = get_history($id);
    respond_ok(['data' => ['conversation' => conversation_to_api($conversation), 'messages' => $messages]]);
}

function chat_send_admin_message(array $params): void
{
    $admin = require_auth();
    $id = (int) $params['id'];
    $b = json_body();
    $message = add_chat_message($id, 'admin', (string) ($b['texte'] ?? ''), $admin);
    respond(['success' => true, 'data' => ['message' => $message]], 201);
}

function chat_update_status(array $params): void
{
    require_auth();
    $id = (int) $params['id'];
    $b = json_body();
    if (!in_array($b['statut'] ?? '', ['ouvert', 'ferme'], true)) {
        respond_error('Ungültiger Status', 400);
    }
    db()->prepare('UPDATE conversations SET statut = :s WHERE id = :id')->execute([':s' => $b['statut'], ':id' => $id]);
    $conversation = fetch_conversation_with_admin($id);
    if (!$conversation) respond_error('Konversation nicht gefunden', 404);
    respond_ok(['data' => ['conversation' => conversation_to_api($conversation)]]);
}

function chat_mark_read(array $params): void
{
    require_auth();
    $id = (int) $params['id'];
    db()->prepare('UPDATE conversations SET non_lu_admin = 0 WHERE id = :id')->execute([':id' => $id]);
    db()->prepare("UPDATE chat_messages SET lu = 1 WHERE conversation_id = :id AND lu = 0 AND expediteur = 'visiteur'")
        ->execute([':id' => $id]);
    respond_ok();
}

function chat_take_over(array $params): void
{
    $admin = require_auth();
    $id = (int) $params['id'];
    db()->prepare('UPDATE conversations SET admin_assigne_id = :a WHERE id = :id')->execute([':a' => $admin['id'], ':id' => $id]);

    $texteAccueil = "Hallo! Mein Name ist {$admin['name']} und ich übernehme jetzt Ihre Anfrage. Wie kann ich Ihnen heute helfen?";
    $message = add_chat_message($id, 'admin', $texteAccueil, $admin);
    $conversation = fetch_conversation_with_admin($id);

    respond_ok(['data' => ['conversation' => conversation_to_api($conversation), 'message' => $message]]);
}
