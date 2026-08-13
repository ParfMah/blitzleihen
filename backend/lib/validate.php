<?php
/**
 * Validation des données entrantes — équivalent de middleware/validate.js
 * (express-validator). Chaque fonction valide un formulaire complet et
 * retourne un tableau associatif d'erreurs (vide = tout est valide).
 */

function is_truthy_checkbox($v): bool
{
    return $v === true || $v === 'true' || $v === 1 || $v === '1';
}

function calc_age(string $isoDate): ?int
{
    $date = DateTime::createFromFormat('Y-m-d', substr($isoDate, 0, 10));
    if (!$date) {
        return null;
    }
    $today = new DateTime();
    return $today->diff($date)->y;
}

function validate_demande(array $b): array
{
    $errors = [];

    foreach (['vorname' => 50, 'nachname' => 50] as $field => $max) {
        $v = trim((string) ($b[$field] ?? ''));
        if ($v === '') {
            $errors[$field] = ucfirst($field) . ' ist erforderlich';
        } elseif (mb_strlen($v) > $max) {
            $errors[$field] = ucfirst($field) . ": max. {$max} Zeichen";
        }
    }

    $geb = (string) ($b['geburtsdatum'] ?? '');
    if ($geb === '') {
        $errors['geburtsdatum'] = 'Geburtsdatum ist erforderlich';
    } else {
        $age = calc_age($geb);
        if ($age === null) {
            $errors['geburtsdatum'] = 'Ungültiges Datumsformat';
        } elseif ($age < 18) {
            $errors['geburtsdatum'] = 'Mindestalter ist 18 Jahre';
        } elseif ($age > 90) {
            $errors['geburtsdatum'] = 'Ungültiges Geburtsdatum';
        }
    }

    $email = trim((string) ($b['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ungültige E-Mail-Adresse';
    }

    $tel = trim((string) ($b['telefon'] ?? ''));
    if ($tel === '' || !preg_match('/^[+]?[\d\s\-()]{8,20}$/', $tel)) {
        $errors['telefon'] = 'Ungültige Telefonnummer';
    }

    foreach (['adresse' => 200, 'ort' => 100] as $field => $max) {
        $v = trim((string) ($b[$field] ?? ''));
        if ($v === '') {
            $errors[$field] = ucfirst($field) . ' ist erforderlich';
        } elseif (mb_strlen($v) > $max) {
            $errors[$field] = ucfirst($field) . ": max. {$max} Zeichen";
        }
    }

    if (trim((string) ($b['land'] ?? '')) === '') {
        $errors['land'] = 'Wohnsitzland ist erforderlich';
    }

    $beschaeftigungOk = [
        'Angestellt (unbefristet)', 'Angestellt (befristet)', 'Selbstständig',
        'Beamter', 'Rentner', 'Student', 'Arbeitssuchend', 'Sonstiges'
    ];
    if (!in_array($b['beschaeftigung'] ?? '', $beschaeftigungOk, true)) {
        $errors['beschaeftigung'] = 'Ungültiger Beschäftigungsstatus';
    }

    if (!isset($b['einkommen']) || !is_numeric($b['einkommen'])) {
        $errors['einkommen'] = 'Einkommen muss eine Zahl sein';
    } else {
        $val = (float) $b['einkommen'];
        if ($val < 0) $errors['einkommen'] = 'Einkommen kann nicht negativ sein';
        if ($val > 1000000) $errors['einkommen'] = 'Ungültiger Einkommenswert';
    }

    $kreditartOk = [
        'Privatkredit', 'Immobilienkredit', 'Autofinanzierung',
        'Renovierungskredit', 'Hypothekenkredit', 'Umschuldung'
    ];
    if (!in_array($b['kreditart'] ?? '', $kreditartOk, true)) {
        $errors['kreditart'] = 'Ungültige Kreditart';
    }

    if (!isset($b['kreditbetrag']) || !is_numeric($b['kreditbetrag'])) {
        $errors['kreditbetrag'] = 'Kreditbetrag muss eine Zahl sein';
    } else {
        $val = (float) $b['kreditbetrag'];
        if ($val < 1000) $errors['kreditbetrag'] = 'Mindestbetrag ist 1.000 €';
        if ($val > 2000000) $errors['kreditbetrag'] = 'Maximalbetrag ist 2.000.000 €';
    }

    if (!isset($b['laufzeit']) || !is_numeric($b['laufzeit'])) {
        $errors['laufzeit'] = 'Laufzeit muss eine Zahl sein';
    } else {
        $val = (int) $b['laufzeit'];
        if ($val < 6) $errors['laufzeit'] = 'Mindestlaufzeit: 6 Monate';
        if ($val > 360) $errors['laufzeit'] = 'Maximallaufzeit: 360 Monate';
    }

    if (!empty($b['verwendungszweck']) && mb_strlen((string) $b['verwendungszweck']) > 1000) {
        $errors['verwendungszweck'] = 'Verwendungszweck: max. 1000 Zeichen';
    }

    if (!is_truthy_checkbox($b['agb'] ?? null)) {
        $errors['agb'] = 'AGB müssen akzeptiert werden';
    }
    if (!is_truthy_checkbox($b['schufa_zustimmung'] ?? null)) {
        $errors['schufa_zustimmung'] = 'SCHUFA-Zustimmung ist erforderlich';
    }

    return $errors;
}

function validate_contact(array $b): array
{
    $errors = [];
    $name = trim((string) ($b['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'Name ist erforderlich';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Name: max. 100 Zeichen';
    }

    $email = trim((string) ($b['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ungültige E-Mail-Adresse';
    }

    $tel = trim((string) ($b['telefon'] ?? ''));
    if ($tel !== '' && !preg_match('/^[+]?[\d\s\-()]{8,20}$/', $tel)) {
        $errors['telefon'] = 'Ungültige Telefonnummer';
    }

    $nachricht = trim((string) ($b['nachricht'] ?? ($b['message'] ?? '')));
    if (mb_strlen($nachricht) < 10 || mb_strlen($nachricht) > 3000) {
        $errors['nachricht'] = 'Nachricht: 10–3000 Zeichen';
    }

    if (!is_truthy_checkbox($b['datenschutz'] ?? null)) {
        $errors['datenschutz'] = 'Datenschutzerklärung muss akzeptiert werden';
    }

    return $errors;
}

function validate_login(array $b): array
{
    $errors = [];
    $email = trim((string) ($b['username'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['username'] = 'Ungültige E-Mail-Adresse';
    }
    if (mb_strlen((string) ($b['password'] ?? '')) < 6) {
        $errors['password'] = 'Passwort: min. 6 Zeichen';
    }
    return $errors;
}
