<?php
/**
 * Copiez ce fichier en "secrets.php" (même dossier) et remplissez les
 * valeurs. secrets.php ne doit JAMAIS être commité sur GitHub — il est
 * déjà listé dans .gitignore.
 */

// --- Base de données MySQL (cPanel → MySQL Databases) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'blitz444_leihen');      // nom exact créé dans cPanel
define('DB_USER', 'blitz444_leihenuser');  // utilisateur exact créé dans cPanel
define('DB_PASS', '');

// --- Sécurité ---
// Chaîne aléatoire longue, générée une fois, jamais partagée.
define('APP_SECRET', '');

// Origines autorisées pour les appels depuis le frontend (CORS)
define('FRONTEND_ORIGINS', 'https://www.blitzleihen.com,https://blitzleihen.com');

// --- Email sortant (SMTP) ---
define('SMTP_HOST', 'mail.blitzleihen.com');
define('SMTP_PORT', 465);
define('SMTP_SECURE', true); // true = SSL (port 465), false = STARTTLS (port 587)
define('SMTP_USER', 'noreply@blitzleihen.com');
define('SMTP_PASS', '');
define('EMAIL_FROM_NAME', 'Blitz Leihen');
define('EMAIL_FROM_ADDRESS', 'noreply@blitzleihen.com');
define('KREDIT_EMAIL', 'kredit@blitzleihen.com');
define('HILFE_EMAIL', 'hilfe@blitzleihen.com');
define('KONTAKT_EMAIL', 'kontakt@blitzleihen.com');

// --- Compte admin créé par scripts/seed.php ---
define('ADMIN_EMAIL', 'info@blitzleihen.com');
define('ADMIN_PASSWORD', 'ChangeMoiImmediatement!');
define('ADMIN_NAME', 'Günther Th. Hoheisel');

// --- Durée de validité d'une session admin, en heures ---
define('TOKEN_LIFETIME_HOURS', 8);
