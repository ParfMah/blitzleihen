<?php
/**
 * Fonctions utilitaires partagées.
 */

function generate_unique_reference(): string
{
    $pdo = db();
    $year = date('Y');
    for ($i = 0; $i < 5; $i++) {
        $num = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $ref = "BL-{$year}-{$num}";
        $stmt = $pdo->prepare('SELECT id FROM demandes WHERE reference_number = :ref LIMIT 1');
        $stmt->execute([':ref' => $ref]);
        if (!$stmt->fetch()) {
            return $ref;
        }
    }
    return 'BL-' . $year . '-' . bin2hex(random_bytes(3));
}

function format_euro($amount): string
{
    return number_format((float) $amount, 2, ',', '.') . ' €';
}

function format_date_de(?string $mysqlDate): string
{
    if (!$mysqlDate) {
        return '';
    }
    $ts = strtotime($mysqlDate);
    return $ts ? date('d.m.Y', $ts) : '';
}

function paginate_params(): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
    return [$page, $limit, ($page - 1) * $limit];
}

/** Redimensionne un tableau associatif MySQL (snake_case) vers le format attendu par le frontend (camelCase), pour une "demande". */
function demande_to_api(array $d): array
{
    return [
        'id' => (string) $d['id'],
        '_id' => (string) $d['id'],
        'referenceNumber' => $d['reference_number'],
        'vorname' => $d['vorname'],
        'nachname' => $d['nachname'],
        'nomComplet' => $d['vorname'] . ' ' . $d['nachname'],
        'geburtsdatum' => $d['geburtsdatum'],
        'staatsangehoerigkeit' => $d['staatsangehoerigkeit'],
        'email' => $d['email'],
        'telefon' => $d['telefon'],
        'adresse' => $d['adresse'],
        'ort' => $d['ort'],
        'land' => $d['land'],
        'beschaeftigung' => $d['beschaeftigung'],
        'einkommen' => (float) $d['einkommen'],
        'bestehendeVerbindlichkeiten' => (float) $d['bestehende_verbindlichkeiten'],
        'kreditart' => $d['kreditart'],
        'kreditbetrag' => (float) $d['kreditbetrag'],
        'kreditbetragFormate' => format_euro($d['kreditbetrag']),
        'laufzeit' => (int) $d['laufzeit'],
        'verwendungszweck' => $d['verwendungszweck'],
        'sms_verification' => $d['sms_verification'],
        'smsVerifie' => (bool) $d['sms_verifie'],
        'datenschutz' => (bool) $d['datenschutz'],
        'agb' => (bool) $d['agb'],
        'schufa_zustimmung' => (bool) $d['schufa_zustimmung'],
        'statut' => $d['statut'],
        'assigneA' => isset($d['assigne_a']) ? (string) $d['assigne_a'] : null,
        'noteInterne' => $d['note_interne'] ?? '',
        'visiteurLocalisationAffichage' => $d['visiteur_localisation_affichage'] ?? '',
        'createdAt' => $d['created_at'],
        'updatedAt' => $d['updated_at'],
    ];
}

function contact_to_api(array $c): array
{
    return [
        'id' => (string) $c['id'],
        '_id' => (string) $c['id'],
        'nom' => $c['nom'],
        'email' => $c['email'],
        'telefon' => $c['telefon'],
        'betreff' => $c['betreff'],
        'nachricht' => $c['nachricht'],
        'statut' => $c['statut'],
        'visiteurLocalisationAffichage' => $c['visiteur_localisation_affichage'] ?? '',
        'createdAt' => $c['created_at'],
        'updatedAt' => $c['updated_at'],
    ];
}
