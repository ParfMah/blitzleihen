<?php
/**
 * Routeur principal — Blitz Leihen API (PHP)
 * Remplace server.js + routes/*.js. Chaque contrôleur est une simple
 * fonction PHP incluse depuis controllers/*.php.
 */

require_once __DIR__ . '/config.php';

apply_cors();

require_once __DIR__ . '/controllers/demandes.php';
require_once __DIR__ . '/controllers/contact.php';
require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/controllers/admin.php';
require_once __DIR__ . '/controllers/settings.php';
require_once __DIR__ . '/controllers/chat.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// On ne garde que ce qui suit "/api" (ou "/health"), quel que soit le
// sous-dossier réel où l'app est installée.
if (preg_match('#/(api/.*|health)$#', rtrim($uri, '/') . '/', $m)) {
    $path = '/' . rtrim($m[1], '/');
} else {
    $path = rtrim($uri, '/');
}
if ($path === '') {
    $path = '/';
}
$segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));

// --------------------------------------------------------------
// Table de routes : [méthode, motif regex, gestionnaire]
// Les groupes nommés (?<id>...) sont passés en paramètres au handler.
// --------------------------------------------------------------
$routes = [
    ['GET', '#^/health$#', 'route_health'],

    // Demandes
    ['POST', '#^/api/demandes$#', 'demandes_create'],
    ['POST', '#^/api/demandes/abandon$#', 'demandes_abandon'],
    ['GET', '#^/api/demandes$#', 'demandes_list'],
    ['GET', '#^/api/demandes/(?<id>\d+)$#', 'demandes_get'],
    ['PUT', '#^/api/demandes/(?<id>\d+)/statut$#', 'demandes_update_statut'],
    ['PUT', '#^/api/demandes/(?<id>\d+)/assigner$#', 'demandes_assign'],
    ['POST', '#^/api/demandes/(?<id>\d+)/note$#', 'demandes_add_note'],
    ['DELETE', '#^/api/demandes/(?<id>\d+)$#', 'demandes_delete'],

    // Contact
    ['POST', '#^/api/contact$#', 'contact_create'],
    ['GET', '#^/api/contact$#', 'contact_list'],
    ['GET', '#^/api/contact/(?<id>\d+)$#', 'contact_get'],
    ['POST', '#^/api/contact/(?<id>\d+)/reponse$#', 'contact_reply'],
    ['PUT', '#^/api/contact/(?<id>\d+)/statut$#', 'contact_update_statut'],

    // Auth
    ['POST', '#^/api/auth/login$#', 'auth_login'],
    ['POST', '#^/api/auth/logout$#', 'auth_logout'],
    ['GET', '#^/api/auth/me$#', 'auth_me'],
    ['POST', '#^/api/auth/refresh$#', 'auth_refresh'],

    // Admin
    ['GET', '#^/api/admin/stats$#', 'admin_stats'],
    ['GET', '#^/api/admin/stats/periode$#', 'admin_stats_periode'],
    ['GET', '#^/api/admin/admins$#', 'admin_list_admins'],
    ['POST', '#^/api/admin/admins$#', 'admin_create_admin'],
    ['PUT', '#^/api/admin/admins/(?<id>\d+)$#', 'admin_update_admin'],
    ['PUT', '#^/api/admin/mot-de-passe$#', 'admin_change_password'],
    ['PUT', '#^/api/admin/mon-profil$#', 'admin_update_profile'],
    ['POST', '#^/api/admin/envoyer-email$#', 'admin_send_email'],
    ['GET', '#^/api/admin/test-smtp$#', 'admin_test_smtp'],

    // Settings
    ['GET', '#^/api/settings/public$#', 'settings_public'],
    ['GET', '#^/api/settings$#', 'settings_get'],
    ['PUT', '#^/api/settings$#', 'settings_update'],

    // Chat — visiteur
    ['POST', '#^/api/chat/conversations$#', 'chat_start_conversation'],
    ['GET', '#^/api/chat/conversations/(?<vid>[A-Za-z0-9_\-]+)$#', 'chat_get_visitor_conversation'],
    ['POST', '#^/api/chat/conversations/(?<vid>[A-Za-z0-9_\-]+)/message$#', 'chat_send_visitor_message'],

    // Chat — admin
    ['GET', '#^/api/chat/admin/conversations$#', 'chat_list_conversations'],
    ['GET', '#^/api/chat/admin/non-lus$#', 'chat_count_unread'],
    ['GET', '#^/api/chat/admin/conversations/(?<id>\d+)$#', 'chat_get_admin_conversation'],
    ['POST', '#^/api/chat/admin/conversations/(?<id>\d+)/message$#', 'chat_send_admin_message'],
    ['PUT', '#^/api/chat/admin/conversations/(?<id>\d+)/statut$#', 'chat_update_status'],
    ['PUT', '#^/api/chat/admin/conversations/(?<id>\d+)/lu$#', 'chat_mark_read'],
    ['PUT', '#^/api/chat/admin/conversations/(?<id>\d+)/prendre-en-charge$#', 'chat_take_over'],
];

function route_health(): void
{
    respond(['success' => true, 'message' => 'Blitz Leihen API (PHP) läuft', 'time' => date(DATE_ATOM)]);
}

foreach ($routes as [$routeMethod, $pattern, $handler]) {
    if ($routeMethod !== $method) {
        continue;
    }
    if (preg_match($pattern, $path, $matches)) {
        $params = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
        $handler($params);
        exit;
    }
}

respond_error("Route nicht gefunden: {$method} {$path}", 404);
