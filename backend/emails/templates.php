<?php
/**
 * Gabarits d'emails HTML — reprend la charte graphique du site
 * (bleu marine / or). Chaque fonction retourne ['subject' => ..., 'html' => ...].
 */

const BRAND_PRIMARY = '#0B2D59';
const BRAND_ACCENT = '#C8A84B';
const BRAND_BG = '#F0F4FA';
const BRAND_TEXT = '#1A1F36';
const BRAND_MUTED = '#4A5478';

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function email_wrapper(string $titre, string $innerHtml): string
{
    $bg = BRAND_BG; $primary = BRAND_PRIMARY; $accent = BRAND_ACCENT; $text = BRAND_TEXT; $muted = BRAND_MUTED;
    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:{$bg};font-family:Arial,Helvetica,sans-serif;color:{$text};">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{$bg};padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
        <tr><td style="background:{$primary};padding:24px 32px;">
          <span style="color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:0.5px;">Blitz Leihen</span>
        </td></tr>
        <tr><td style="padding:32px;">
          <h1 style="font-size:18px;color:{$primary};margin:0 0 16px;">{$titre}</h1>
          {$innerHtml}
        </td></tr>
        <tr><td style="background:{$bg};padding:20px 32px;font-size:12px;color:{$muted};border-top:1px solid #e2e8f0;">
          Blitz Leihen GmbH &middot; Kaiserhofstr. 13 &middot; 60313 Frankfurt am Main<br>
          info@blitzleihen.com &middot; +49 (0) 69 1200 656 0
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function row(string $label, string $value): string
{
    $muted = BRAND_MUTED;
    return '<tr><td style="padding:6px 0;color:' . $muted . ';font-size:13px;width:180px;">' . e($label) . '</td>'
        . '<td style="padding:6px 0;font-size:14px;">' . e($value) . '</td></tr>';
}

function email_confirmation_client(array $d): array
{
    $subject = "✅ Ihr Kreditantrag wurde eingegangen — Referenz {$d['reference_number']}";
    $inner = '<p style="font-size:14px;line-height:1.6;">Hallo ' . e($d['vorname']) . ',</p>'
        . '<p style="font-size:14px;line-height:1.6;">vielen Dank für Ihren Kreditantrag bei Blitz Leihen. Wir haben Ihre Anfrage erhalten und werden sie zeitnah prüfen.</p>'
        . '<table role="presentation" width="100%" style="margin:20px 0;background:' . BRAND_BG . ';border-radius:6px;padding:16px;">'
        . row('Referenznummer', $d['reference_number'])
        . row('Kreditart', $d['kreditart'])
        . row('Betrag', format_euro($d['kreditbetrag']))
        . row('Laufzeit', $d['laufzeit'] . ' Monate')
        . '</table>'
        . '<p style="font-size:14px;line-height:1.6;">Ein Berater wird sich in Kürze bei Ihnen melden. Bei Fragen erreichen Sie uns jederzeit unter info@blitzleihen.com.</p>';
    return ['subject' => $subject, 'html' => email_wrapper('Ihr Kreditantrag ist eingegangen', $inner)];
}

function email_notification_conseiller(array $d): array
{
    $subject = "🔔 Neuer Kreditantrag — {$d['kreditart']} " . format_euro($d['kreditbetrag']) . " — {$d['vorname']} {$d['nachname']}";
    $inner = '<table role="presentation" width="100%">'
        . row('Referenz', $d['reference_number'])
        . row('Name', $d['vorname'] . ' ' . $d['nachname'])
        . row('E-Mail', $d['email'])
        . row('Telefon', $d['telefon'])
        . row('Kreditart', $d['kreditart'])
        . row('Betrag', format_euro($d['kreditbetrag']))
        . row('Laufzeit', $d['laufzeit'] . ' Monate')
        . row('Monatl. Einkommen', format_euro($d['einkommen']))
        . row('Beschäftigung', $d['beschaeftigung'])
        . row('Ort', $d['ort'] . ', ' . $d['land'])
        . '</table>'
        . '<p style="font-size:13px;color:' . BRAND_MUTED . ';margin-top:16px;">Im Admin-Bereich einsehen und bearbeiten.</p>';
    return ['subject' => $subject, 'html' => email_wrapper('Neuer Kreditantrag', $inner)];
}

function email_changement_statut(array $d): array
{
    $info = [
        'Analyse' => ['emoji' => '🔍', 'titre' => 'Ihr Antrag wird geprüft', 'texte' => 'Ihr Kreditantrag befindet sich aktuell in der Prüfung. Wir melden uns, sobald eine Entscheidung vorliegt.'],
        'Akzeptiert' => ['emoji' => '✅', 'titre' => 'Ihr Antrag wurde angenommen', 'texte' => 'Wir freuen uns, Ihnen mitteilen zu können, dass Ihr Kreditantrag angenommen wurde. Ein Berater wird sich in Kürze bei Ihnen melden, um die nächsten Schritte zu besprechen.'],
        'Abgelehnt' => ['emoji' => '❌', 'titre' => 'Ihr Antrag wurde abgelehnt', 'texte' => 'Nach sorgfältiger Prüfung können wir Ihren Kreditantrag leider nicht bewilligen. Bei Fragen zu dieser Entscheidung kontaktieren Sie uns gerne.'],
        'Neu' => ['emoji' => '📋', 'titre' => 'Ihr Antrag wurde registriert', 'texte' => 'Ihr Kreditantrag wurde registriert.'],
    ];
    $i = $info[$d['statut']] ?? $info['Neu'];
    $subject = "{$i['emoji']} {$i['titre']} — Referenz {$d['reference_number']}";
    $inner = '<p style="font-size:14px;line-height:1.6;">Hallo ' . e($d['vorname']) . ',</p>'
        . '<p style="font-size:14px;line-height:1.6;">' . e($i['texte']) . '</p>'
        . '<table role="presentation" width="100%" style="margin:20px 0;background:' . BRAND_BG . ';border-radius:6px;padding:16px;">'
        . row('Referenznummer', $d['reference_number'])
        . row('Neuer Status', $d['statut'])
        . '</table>';
    return ['subject' => $subject, 'html' => email_wrapper($i['titre'], $inner)];
}

function email_message_contact(array $data): array
{
    $sujetClient = $data['betreff'] !== '—' ? $data['betreff'] : 'Allgemein';
    $subject = "✉️ Neue Kontaktanfrage — {$sujetClient} — {$data['name']}";
    $inner = '<table role="presentation" width="100%">'
        . row('Name', $data['name'])
        . row('E-Mail', $data['email'])
        . row('Telefon', $data['telefon'])
        . row('Betreff', $data['betreff'])
        . '</table>'
        . '<p style="font-size:14px;line-height:1.6;margin-top:16px;white-space:pre-line;">' . e($data['nachricht']) . '</p>';
    return ['subject' => $subject, 'html' => email_wrapper('Neue Kontaktanfrage', $inner)];
}

function email_confirmation_contact(array $data): array
{
    $subject = '✅ Wir haben Ihre Nachricht erhalten — Blitz Leihen';
    $inner = '<p style="font-size:14px;line-height:1.6;">Hallo ' . e($data['name']) . ',</p>'
        . '<p style="font-size:14px;line-height:1.6;">vielen Dank für Ihre Nachricht. Wir melden uns innerhalb von 24 Stunden bei Ihnen.</p>'
        . '<p style="font-size:13px;color:' . BRAND_MUTED . ';margin-top:16px;">Ihre Nachricht: „' . e(mb_strimwidth($data['nachricht'], 0, 200, '…')) . '"</p>';
    return ['subject' => $subject, 'html' => email_wrapper('Nachricht erhalten', $inner)];
}

function email_reponse_contact(string $texte, string $sujet): array
{
    $inner = '<div style="font-size:14px;line-height:1.6;white-space:pre-line;">' . e($texte) . '</div>';
    return ['subject' => $sujet, 'html' => email_wrapper('Antwort von Blitz Leihen', $inner)];
}

function email_libre(string $message, string $sujet): array
{
    $inner = '<div style="font-size:14px;line-height:1.6;white-space:pre-line;">' . e($message) . '</div>';
    return ['subject' => $sujet, 'html' => email_wrapper($sujet, $inner)];
}
