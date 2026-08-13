<?php
/**
 * Réglages globaux (ex : chat activé/désactivé) — remplace controllers/settingsController.js
 */

function get_settings_row(): array
{
    $pdo = db();
    $stmt = $pdo->query("SELECT * FROM settings WHERE cle = 'global' LIMIT 1");
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->exec("INSERT INTO settings (cle, chat_actif) VALUES ('global', 1)");
        $stmt = $pdo->query("SELECT * FROM settings WHERE cle = 'global' LIMIT 1");
        $row = $stmt->fetch();
    }
    return $row;
}

function settings_public(): void
{
    $row = get_settings_row();
    respond_ok(['data' => ['chatActif' => (bool) $row['chat_actif']]]);
}

function settings_get(): void
{
    require_auth();
    $row = get_settings_row();
    respond_ok(['data' => ['settings' => ['chatActif' => (bool) $row['chat_actif']]]]);
}

function settings_update(): void
{
    require_auth();
    $b = json_body();
    if (array_key_exists('chatActif', $b)) {
        $val = $b['chatActif'] ? 1 : 0;
        db()->prepare("UPDATE settings SET chat_actif = :v WHERE cle = 'global'")->execute([':v' => $val]);
    }
    $row = get_settings_row();
    respond_ok(['data' => ['settings' => ['chatActif' => (bool) $row['chat_actif']]]], 'Einstellungen erfolgreich gespeichert');
}
