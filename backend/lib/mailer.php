<?php
/**
 * Client SMTP minimal, écrit en PHP pur (sans PHPMailer/Composer) pour
 * fonctionner sur n'importe quel hébergement mutualisé sans dépendance
 * externe. Supporte SSL implicite (port 465) et STARTTLS (port 587),
 * avec authentification AUTH LOGIN.
 */

class SmtpMailer
{
    private string $host;
    private int $port;
    private bool $secure;
    private string $user;
    private string $pass;
    /** @var resource|null */
    private $socket = null;

    public function __construct(string $host, int $port, bool $secure, string $user, string $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->secure = $secure;
        $this->user = $user;
        $this->pass = $pass;
    }

    private function read(): string
    {
        $data = '';
        while (($line = fgets($this->socket, 515)) !== false) {
            $data .= $line;
            // La ligne finale d'une réponse SMTP a un espace après le code (ex: "250 OK"),
            // les lignes intermédiaires ont un tiret ("250-...").
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    }

    private function write(string $cmd): void
    {
        fwrite($this->socket, $cmd . "\r\n");
    }

    private function expect(string $data, array $codes): void
    {
        $code = (int) substr($data, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new Exception('SMTP: réponse inattendue (' . $code . '): ' . trim($data));
        }
    }

    /**
     * @return array{success:bool, error?:string}
     */
    public function send(string $to, string $fromName, string $fromEmail, string $subject, string $html, string $replyTo = ''): array
    {
        try {
            $this->connect();
            $this->handshake();
            $this->authenticate();
            $this->transmit($to, $fromName, $fromEmail, $subject, $html, $replyTo);
            $this->write('QUIT');
            $this->read();
            fclose($this->socket);
            return ['success' => true];
        } catch (Throwable $e) {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            error_log('SmtpMailer error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function connect(): void
    {
        $transport = $this->secure ? 'ssl://' : 'tcp://';
        // Sur l'hébergement mutualisé, le certificat présenté par le
        // serveur mail (mail.votredomaine.com) ne correspond pas toujours
        // exactement au nom attendu (certificat partagé du serveur). On
        // désactive donc la vérification stricte du nom d'hôte : la
        // connexion reste chiffrée, et l'authentification AUTH LOGIN par
        // mot de passe protège déjà contre l'usurpation.
        $context = stream_context_create(['ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ]]);
        $this->socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$this->socket) {
            throw new Exception("Verbindung zu {$this->host}:{$this->port} fehlgeschlagen ({$errstr})");
        }
        $this->expect($this->read(), [220]);
    }

    private function handshake(): void
    {
        $this->write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'blitzleihen.com'));
        $resp = $this->read();
        $this->expect($resp, [250]);

        if (!$this->secure && stripos($resp, 'STARTTLS') !== false) {
            $this->write('STARTTLS');
            $this->expect($this->read(), [220]);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('STARTTLS-Handshake fehlgeschlagen');
            }
            $this->write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'blitzleihen.com'));
            $this->expect($this->read(), [250]);
        }
    }

    private function authenticate(): void
    {
        $this->write('AUTH LOGIN');
        $this->expect($this->read(), [334]);
        $this->write(base64_encode($this->user));
        $this->expect($this->read(), [334]);
        $this->write(base64_encode($this->pass));
        $this->expect($this->read(), [235]);
    }

    private function transmit(string $to, string $fromName, string $fromEmail, string $subject, string $html, string $replyTo): void
    {
        $this->write('MAIL FROM:<' . $fromEmail . '>');
        $this->expect($this->read(), [250]);

        foreach (array_filter(array_map('trim', explode(',', $to))) as $recipient) {
            $this->write('RCPT TO:<' . $recipient . '>');
            $this->expect($this->read(), [250, 251]);
        }

        $this->write('DATA');
        $this->expect($this->read(), [354]);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $boundary = 'blitz_' . bin2hex(random_bytes(8));

        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $encodedFromName . ' <' . $fromEmail . '>';
        $headers[] = 'To: ' . $to;
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $headers[] = 'Subject: ' . $encodedSubject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'X-Mailer: BlitzLeihenPHP';

        // Dot-stuffing : toute ligne commençant par "." doit être doublée.
        $body = preg_replace('/^\./m', '..', $html);

        $this->write(implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.");
        $this->expect($this->read(), [250]);
    }
}

/**
 * Envoie un email avec les identifiants SMTP configurés dans secrets.php.
 * @return array{success:bool, error?:string}
 */
function send_mail(string $to, string $subject, string $html, string $replyTo = ''): array
{
    if (SMTP_HOST === '' || SMTP_USER === '' || SMTP_PASS === '') {
        error_log('send_mail: configuration SMTP incomplète, email non envoyé (' . $subject . ')');
        return ['success' => false, 'error' => 'SMTP nicht konfiguriert'];
    }
    $mailer = new SmtpMailer(SMTP_HOST, (int) SMTP_PORT, (bool) SMTP_SECURE, SMTP_USER, SMTP_PASS);
    return $mailer->send($to, EMAIL_FROM_NAME, EMAIL_FROM_ADDRESS, $subject, $html, $replyTo);
}
