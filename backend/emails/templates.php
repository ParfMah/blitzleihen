<?php
/**
 * Gabarits d'emails HTML — reprend exactement la charte graphique et le design complet
 * du backend Node.js d'origine (bleu marine / or).
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

function email_wrapper_v2(string $title, string $subtitle, string $innerHtml): string
{
    $bg = BRAND_BG; 
    $primary = BRAND_PRIMARY; 
    $accent = BRAND_ACCENT; 
    $text = BRAND_TEXT; 
    $muted = BRAND_MUTED;

    // 🔗 URLs Cloudinary Haute Définition
    $headerImageUrl = "https://res.cloudinary.com/duramdsjz/image/upload/f_gif,q_auto,w_1200,c_limit/v1786635754/Blitzleihen_entete.webp"; 
    $footerImageUrl = "https://res.cloudinary.com/duramdsjz/image/upload/f_auto,q_auto,w_1200,c_limit/v1786635754/Blitzleihen_pied_de_page.webp"; 

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Animation de transition douce entre Marron doux, Jaune et Orange */
        @keyframes smoothColorShift {
            0%   { background-color: #F5EBE6; } /* Marron / Cappuccino très doux */
            33%  { background-color: #FEF9E7; } /* Jaune chaud dore */
            66%  { background-color: #FDF2E9; } /* Orange / Pêche doux */
            100% { background-color: #F5EBE6; }
        }
        .animated-bg {
            background-color: #F5EBE6; /* Fallback fixe pour Gmail et Outlook */
            animation: smoothColorShift 9s infinite ease-in-out;
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:{$bg};font-family:Arial,Helvetica,sans-serif;color:{$text};">
  
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{$bg};padding:20px 0;">
    <tr><td align="center">
      
      <!-- CONTENEUR PRINCIPAL AVEC BORDURE ENCADRÉE DORÉE -->
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);width:100%;max-width:600px;border:3px solid {$accent};border-collapse:separate;">
        
        <!-- HEADER PLEINE LARGEUR -->
        <tr>
          <td align="center" width="100%" style="background:{$primary};padding:0;margin:0;line-height:0;font-size:0;">
            <img src="{$headerImageUrl}" alt="Blitz Leihen" width="100%" style="display:block; width:100% !important; min-width:100%; max-width:100%; height:auto; border:0; outline:none;">
          </td>
        </tr>

        <!-- CONTENU EN PLEINE LARGEUR AVEC FOND ANIMÉ ET BORDS ALIGNÉS -->
        <tr>
          <td class="animated-bg" width="100%" style="padding:32px 24px;background-color:#F5EBE6;box-sizing:border-box;">
            {$innerHtml}
          </td>
        </tr>

        <!-- FOOTER PLEINE LARGEUR -->
        <tr>
          <td align="center" width="100%" style="background:{$primary};padding:0;margin:0;line-height:0;font-size:0;">
            <img src="{$footerImageUrl}" alt="Blitz Leihen Footer" width="100%" style="display:block; width:100% !important; min-width:100%; max-width:100%; height:auto; border:0; outline:none;">
          </td>
        </tr>

        <!-- MENTIONS LÉGALES -->
        <tr>
          <td align="center" style="background:{$primary};padding:20px;font-size:11px;color:#ffffff;line-height:1.6;">
            <strong>Blitz Leihen GmbH</strong> &middot; Kaiserhofstr. 13 &middot; 60313 Frankfurt am Main<br>
            📞 +49 (0) 69 1200 656 0 &middot; ✉ <a href="mailto:info@blitzleihen.com" style="color:{$accent};text-decoration:none;">info@blitzleihen.com</a><br><br>
            <span style="color:#88a0c0;">Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese Nachricht.</span>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function row_table(string $label, string $value, bool $isBg = false): string
{
    $bgStyle = $isBg ? 'background-color:#f8f9fa;' : '';
    return '<tr style="' . $bgStyle . '">'
        . '<td style="padding:8px 10px;color:' . BRAND_MUTED . ';font-size:12px;font-weight:bold;width:40%;text-transform:uppercase;">' . e($label) . '</td>'
        . '<td style="padding:8px 10px;font-size:13px;color:#111111;">' . e($value) . '</td>'
        . '</tr>';
}

function email_confirmation_client(array $d): array
{
    $subject = "✅ Ihr Kreditantrag wurde eingegangen — Referenz {$d['reference_number']}";

    // Formatage des données
    $vorname = $d['vorname'] ?? '';
    $nachname = $d['nachname'] ?? '';
    $fullName = trim("{$vorname} {$nachname}");
    
    $dob = !empty($d['geburtsdatum']) ? date('d.m.Y', strtotime($d['geburtsdatum'])) : '—';
    $adresse = trim(($d['adresse'] ?? '') . ' ' . ($d['ort'] ?? ''));
    $land = !empty($d['land']) ? ucfirst($d['land']) : 'Deutschland';
    
    $einkommen = isset($d['einkommen']) ? format_euro($d['einkommen']) . ' / Monat' : '—';
    $betrag = isset($d['kreditbetrag']) ? format_euro($d['kreditbetrag']) : '—';
    $laufzeit = isset($d['laufzeit']) ? $d['laufzeit'] . ' Monate' : '—';
    $verwendungszweck = !empty($d['verwendungszweck']) ? $d['verwendungszweck'] : '—';
    $smsNotice = ($d['sms_verification'] ?? 'nein') === 'ja' ? 'Aktiviert' : 'Nicht aktiviert';
    $dateAntrag = !empty($d['created_at']) ? date('d.m.Y, H:i', strtotime($d['created_at'])) : date('d.m.Y, H:i');

    $inner = '
        <h2 style="color:' . BRAND_PRIMARY . ';font-size:20px;margin-top:0;text-align:center;">
            Vielen Dank für Ihren Antrag, ' . e($vorname) . '!
        </h2>
        
        <p style="color:#555555;font-size:14px;line-height:1.6;text-align:center;margin-bottom:25px;">
            Wir haben Ihren Kreditantrag erhalten und werden ihn innerhalb von <strong>24 Stunden</strong> prüfen. Unser Team wird sich anschließend direkt bei Ihnen melden.
        </p>

        <!-- ENCADRÉ RÉFÉRENCE GOLD -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:30px;">
            <tr>
                <td align="center" style="border:2px solid ' . BRAND_ACCENT . ';background-color:#f8f9fa;border-radius:10px;padding:18px;">
                    <div style="font-size:11px;color:' . BRAND_MUTED . ';text-transform:uppercase;letter-spacing:1px;font-weight:bold;">Ihre Referenznummer</div>
                    <div style="font-size:24px;color:' . BRAND_PRIMARY . ';font-weight:bold;letter-spacing:2px;margin:5px 0;">' . e($d['reference_number']) . '</div>
                    <div style="font-size:11px;color:' . BRAND_MUTED . ';">Bitte bewahren Sie diese Nummer für alle Rückfragen auf.</div>
                </td>
            </tr>
        </table>

        <!-- PERSÖNLICHE ANGABEN -->
        <h3 style="color:' . BRAND_PRIMARY . ';font-size:13px;text-transform:uppercase;border-bottom:2px solid ' . BRAND_ACCENT . ';padding-bottom:5px;margin-bottom:12px;letter-spacing:0.5px;">
            Persönliche Angaben
        </h3>
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:25px;border-collapse:collapse;">
            ' . row_table('Name', $fullName, true) . '
            ' . row_table('Geburtsdatum', $dob, false) . '
            ' . row_table('E-Mail', $d['email'] ?? '—', true) . '
            ' . row_table('Telefon', $d['telefon'] ?? '—', false) . '
            ' . row_table('Adresse', $adresse, true) . '
            ' . row_table('Wohnsitzland', $land, false) . '
            ' . row_table('Beschäftigung', $d['beschaeftigung'] ?? '—', true) . '
            ' . row_table('Nettoeinkommen', $einkommen, false) . '
        </table>

        <!-- KREDITANGABEN -->
        <h3 style="color:' . BRAND_PRIMARY . ';font-size:13px;text-transform:uppercase;border-bottom:2px solid ' . BRAND_ACCENT . ';padding-bottom:5px;margin-bottom:12px;letter-spacing:0.5px;">
            Kreditangaben
        </h3>
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:30px;border-collapse:collapse;">
            ' . row_table('Kreditart', $d['kreditart'] ?? 'Privatkredit', true) . '
            ' . row_table('Kreditbetrag', $betrag, false) . '
            ' . row_table('Laufzeit', $laufzeit, true) . '
            ' . row_table('Verwendungszweck', $verwendungszweck, false) . '
            ' . row_table('SMS-Benachrichtigungen', $smsNotice, true) . '
            ' . row_table('Antragsdatum', $dateAntrag, false) . '
        </table>

        <!-- WAS PASSIERT ALS NÄCHSTES -->
        <h3 style="color:' . BRAND_PRIMARY . ';font-size:13px;text-transform:uppercase;border-bottom:2px solid ' . BRAND_ACCENT . ';padding-bottom:5px;margin-bottom:18px;letter-spacing:0.5px;">
            Was passiert als Nächstes?
        </h3>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:12px;">
            <tr>
                <td width="35" valign="top">
                    <div style="background-color:' . BRAND_PRIMARY . ';color:#ffffff;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;font-weight:bold;font-size:12px;">1</div>
                </td>
                <td style="padding-left:8px;">
                    <strong style="color:' . BRAND_PRIMARY . ';font-size:13px;">Prüfung</strong><br>
                    <span style="color:#666;font-size:12px;">Unser Team prüft Ihren Antrag innerhalb von 24 Stunden.</span>
                </td>
            </tr>
        </table>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:12px;">
            <tr>
                <td width="35" valign="top">
                    <div style="background-color:' . BRAND_PRIMARY . ';color:#ffffff;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;font-weight:bold;font-size:12px;">2</div>
                </td>
                <td style="padding-left:8px;">
                    <strong style="color:' . BRAND_PRIMARY . ';font-size:13px;">Kontaktaufnahme</strong><br>
                    <span style="color:#666;font-size:12px;">Ein persönlicher Berater wird sich bei Ihnen melden.</span>
                </td>
            </tr>
        </table>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:12px;">
            <tr>
                <td width="35" valign="top">
                    <div style="background-color:' . BRAND_PRIMARY . ';color:#ffffff;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;font-weight:bold;font-size:12px;">3</div>
                </td>
                <td style="padding-left:8px;">
                    <strong style="color:' . BRAND_PRIMARY . ';font-size:13px;">Angebot</strong><br>
                    <span style="color:#666;font-size:12px;">Sie erhalten ein maßgeschneidertes Kreditangebot.</span>
                </td>
            </tr>
        </table>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:30px;">
            <tr>
                <td width="35" valign="top">
                    <div style="background-color:' . BRAND_PRIMARY . ';color:#ffffff;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;font-weight:bold;font-size:12px;">4</div>
                </td>
                <td style="padding-left:8px;">
                    <strong style="color:' . BRAND_PRIMARY . ';font-size:13px;">Auszahlung</strong><br>
                    <span style="color:#666;font-size:12px;">Nach Ihrer Unterschrift: Geldeingang in 48 Stunden.</span>
                </td>
            </tr>
        </table>

        <!-- BUTTON CONTACT -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f0f4f8;border-radius:8px;padding:20px;text-align:center;">
            <tr>
                <td>
                    <p style="margin:0 0 12px 0;font-size:13px;color:#555;">Fragen zu Ihrem Antrag? Unser Team hilft Ihnen gerne weiter.</p>
                    <a href="mailto:info@blitzleihen.com" style="background-color:' . BRAND_PRIMARY . ';color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:5px;font-weight:bold;font-size:12px;display:inline-block;">✉ Uns kontaktieren</a>
                </td>
            </tr>
        </table>
    ';

    return [
        'subject' => $subject,
        'html'    => email_wrapper_v2('Ihr Kreditantrag ist eingegangen', '', $inner)
    ];
}

function email_notification_conseiller(array $d): array
{
    $subject = "🔔 Neuer Kreditantrag — {$d['kreditart']} " . format_euro($d['kreditbetrag']) . " — {$d['vorname']} {$d['nachname']}";
    $inner = '<table role="presentation" width="100%">'
        . row_table('Referenz', $d['reference_number'])
        . row_table('Name', $d['vorname'] . ' ' . $d['nachname'])
        . row_table('E-Mail', $d['email'])
        . row_table('Telefon', $d['telefon'])
        . row_table('Kreditart', $d['kreditart'])
        . row_table('Betrag', format_euro($d['kreditbetrag']))
        . row_table('Laufzeit', $d['laufzeit'] . ' Monate')
        . row_table('Monatl. Einkommen', format_euro($d['einkommen']))
        . row_table('Beschäftigung', $d['beschaeftigung'])
        . row_table('Ort', $d['ort'] . ', ' . $d['land'])
        . '</table>'
        . '<p style="font-size:13px;color:' . BRAND_MUTED . ';margin-top:16px;">Im Admin-Bereich einsehen und bearbeiten.</p>';
    return ['subject' => $subject, 'html' => email_wrapper_v2('Neuer Kreditantrag', '', $inner)];
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
        . row_table('Referenznummer', $d['reference_number'])
        . row_table('Neuer Status', $d['statut'])
        . '</table>';
    return ['subject' => $subject, 'html' => email_wrapper_v2($i['titre'], '', $inner)];
}

function email_message_contact(array $data): array
{
    $sujetClient = $data['betreff'] !== '—' ? $data['betreff'] : 'Allgemein';
    $subject = "✉️ Neue Kontaktanfrage — {$sujetClient} — {$data['name']}";
    $inner = '<table role="presentation" width="100%">'
        . row_table('Name', $data['name'])
        . row_table('E-Mail', $data['email'])
        . row_table('Telefon', $data['telefon'])
        . row_table('Betreff', $data['betreff'])
        . '</table>'
        . '<p style="font-size:14px;line-height:1.6;margin-top:16px;white-space:pre-line;">' . e($data['nachricht']) . '</p>';
    return ['subject' => $subject, 'html' => email_wrapper_v2('Neue Kontaktanfrage', '', $inner)];
}

function email_confirmation_contact(array $data): array
{
    $subject = '✅ Wir haben Ihre Nachricht erhalten — Blitz Leihen';
    $inner = '<p style="font-size:14px;line-height:1.6;">Hallo ' . e($data['name']) . ',</p>'
        . '<p style="font-size:14px;line-height:1.6;">vielen Dank für Ihre Nachricht. Wir melden uns innerhalb von 24 Stunden bei Ihnen.</p>'
        . '<p style="font-size:13px;color:' . BRAND_MUTED . ';margin-top:16px;">Ihre Nachricht: „' . e(mb_strimwidth($data['nachricht'], 0, 200, '…')) . '"</p>';
    return ['subject' => $subject, 'html' => email_wrapper_v2('Nachricht erhalten', '', $inner)];
}

function email_reponse_contact(string $texte, string $sujet): array
{
    $inner = '<div style="font-size:14px;line-height:1.6;white-space:pre-line;">' . e($texte) . '</div>';
    return ['subject' => $sujet, 'html' => email_wrapper_v2('Antwort von Blitz Leihen', '', $inner)];
}

function email_libre(string $message, string $sujet): array
{
    $inner = '<div style="font-size:14px;line-height:1.6;white-space:pre-line;">' . e($message) . '</div>';
    return ['subject' => $sujet, 'html' => email_wrapper_v2($sujet, '', $inner)];
}