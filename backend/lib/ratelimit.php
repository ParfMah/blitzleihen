<?php
/**
 * Anti-spam simple par IP, basé sur MySQL (remplace express-rate-limit).
 * Coupe la requête avec 429 si la limite est dépassée.
 */

function enforce_rate_limit(string $bucket, int $maxRequests, int $windowMinutes, string $friendlyMessage): void
{
    $ip = client_ip();
    if ($ip === '') {
        return; // pas d'IP identifiable, on laisse passer plutôt que de bloquer un usage légitime
    }
    $ipHash = hash('sha256', $ip);
    $pdo = db();

    // Nettoyage occasionnel des vieilles entrées (1 requête sur 50 environ)
    if (random_int(1, 50) === 1) {
        $pdo->exec("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS n FROM rate_limits
         WHERE bucket = :bucket AND ip_hash = :ip AND created_at > DATE_SUB(NOW(), INTERVAL :mins MINUTE)'
    );
    $stmt->bindValue(':bucket', $bucket);
    $stmt->bindValue(':ip', $ipHash);
    $stmt->bindValue(':mins', $windowMinutes, PDO::PARAM_INT);
    $stmt->execute();
    $count = (int) $stmt->fetch()['n'];

    if ($count >= $maxRequests) {
        respond_error($friendlyMessage, 429);
    }

    $insert = $pdo->prepare('INSERT INTO rate_limits (bucket, ip_hash) VALUES (:bucket, :ip)');
    $insert->execute([':bucket' => $bucket, ':ip' => $ipHash]);
}
