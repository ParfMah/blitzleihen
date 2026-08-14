<?php

/**
 * Configuration principale — Blitz Leihen (backend PHP)
 * Ne contient aucun secret : tout vient de secrets.php (jamais commité,
 * créé une seule fois directement sur le serveur — voir secrets.example.php).
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // jamais afficher les erreurs PHP brutes au visiteur
date_default_timezone_set('Europe/Berlin');

$secretsFile = __DIR__ . '/secrets.php';
if (!file_exists($secretsFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Configuration serveur manquante : secrets.php absent. Copiez secrets.example.php vers secrets.php et remplissez-le.'
    ]);
    exit;
}
require_once $secretsFile;

// Valeurs par défaut si absentes de secrets.php
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_NAME') or define('DB_NAME', '');
defined('DB_USER') or define('DB_USER', '');
defined('DB_PASS') or define('DB_PASS', '');

defined('APP_SECRET') or define('APP_SECRET', '');
defined('FRONTEND_ORIGINS') or define('FRONTEND_ORIGINS', 'https://www.blitzleihen.com,https://blitzleihen.com');

defined('SMTP_HOST') or define('SMTP_HOST', '');
defined('SMTP_PORT') or define('SMTP_PORT', 465);
defined('SMTP_SECURE') or define('SMTP_SECURE', true);
defined('SMTP_USER') or define('SMTP_USER', '');
defined('SMTP_PASS') or define('SMTP_PASS', '');
defined('EMAIL_FROM_NAME') or define('EMAIL_FROM_NAME', 'Blitz Leihen');
defined('EMAIL_FROM_ADDRESS') or define('EMAIL_FROM_ADDRESS', '');
defined('KREDIT_EMAIL') or define('KREDIT_EMAIL', '');   // réception/suivi des demandes de prêt + pièces justificatives
defined('HILFE_EMAIL') or define('HILFE_EMAIL', '');     // assistance pour un dossier déjà initié
defined('KONTAKT_EMAIL') or define('KONTAKT_EMAIL', ''); // page Kontakt, demandes générales

defined('ADMIN_EMAIL') or define('ADMIN_EMAIL', '');
defined('ADMIN_PASSWORD') or define('ADMIN_PASSWORD', '');
defined('ADMIN_NAME') or define('ADMIN_NAME', 'Administrateur');

defined('TOKEN_LIFETIME_HOURS') or define('TOKEN_LIFETIME_HOURS', 8);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/response.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/mailer.php';
require_once __DIR__ . '/lib/ratelimit.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/emails/templates.php';
