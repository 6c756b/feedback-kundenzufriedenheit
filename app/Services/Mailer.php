<?php

namespace App\Services;

class Mailer
{
    private const TIMEOUT = 15;

    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $fromEmail,
        string $fromName
    ): bool {
        $config = require ROOT . '/config.php';
        $smtp   = $config['smtp'] ?? [];

        $host = $smtp['host'] ?? '';
        $port = (int)($smtp['port'] ?? 587);
        $user = $smtp['user'] ?? '';
        $pass = $smtp['password'] ?? '';

        if (empty($host)) {
            return false;
        }

        $raw = self::buildMessage($fromEmail, $fromName, $toEmail, $toName, $subject, $htmlBody);
        return self::transmit($host, $port, $user, $pass, $fromEmail, $toEmail, $raw);
    }

    private static function buildMessage(
        string $from, string $fromName,
        string $to,   string $toName,
        string $subject, string $html
    ): string {
        $enc = fn(string $s) => preg_match('/[^\x20-\x7E]/', $s)
            ? '=?UTF-8?B?' . base64_encode($s) . '?='
            : $s;

        $headers  = 'Date: '    . date('r') . "\r\n";
        $headers .= 'From: '    . $enc($fromName) . ' <' . $from . '>' . "\r\n";
        $headers .= 'To: '      . $enc($toName)   . ' <' . $to   . '>' . "\r\n";
        $headers .= 'Subject: ' . $enc($subject)          . "\r\n";
        $headers .= 'MIME-Version: 1.0'                   . "\r\n";
        $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'Content-Transfer-Encoding: base64'   . "\r\n";

        $body = chunk_split(base64_encode($html), 76, "\r\n");

        return $headers . "\r\n" . $body;
    }

    private static function transmit(
        string $host, int $port,
        string $user, string $pass,
        string $from, string $to,
        string $raw
    ): bool {
        $errno = 0; $errstr = '';
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
        ]]);

        if ($port === 465) {
            $sock = @stream_socket_client(
                "ssl://{$host}:{$port}", $errno, $errstr, self::TIMEOUT,
                STREAM_CLIENT_CONNECT, $ctx
            );
        } else {
            $sock = @stream_socket_client(
                "tcp://{$host}:{$port}", $errno, $errstr, self::TIMEOUT
            );
        }

        if (!$sock) {
            return false;
        }
        stream_set_timeout($sock, self::TIMEOUT);

        // Reads lines until the last line of a (possibly multi-line) SMTP response
        $read = function () use ($sock): string {
            $last = '';
            while (($line = fgets($sock, 1024)) !== false) {
                $last = $line;
                if (strlen($line) < 4 || $line[3] !== '-') break;
            }
            return $last;
        };

        $cmd = fn(string $c) => fwrite($sock, $c . "\r\n");
        $ok  = fn(string $code): bool => str_starts_with($read(), $code);

        if (!$ok('220'))                          { fclose($sock); return false; }

        $domain = gethostname() ?: 'localhost';
        $cmd("EHLO {$domain}");
        if (!$ok('250'))                          { fclose($sock); return false; }

        if ($port === 587) {
            $cmd('STARTTLS');
            if (!$ok('220'))                      { fclose($sock); return false; }
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($sock); return false;
            }
            $cmd("EHLO {$domain}");
            if (!$ok('250'))                      { fclose($sock); return false; }
        }

        if ($user !== '' && $pass !== '') {
            $cmd('AUTH LOGIN');
            if (!$ok('334'))                      { fclose($sock); return false; }
            $cmd(base64_encode($user));
            if (!$ok('334'))                      { fclose($sock); return false; }
            $cmd(base64_encode($pass));
            if (!$ok('235'))                      { fclose($sock); return false; }
        }

        $cmd("MAIL FROM:<{$from}>");
        if (!$ok('250'))                          { fclose($sock); return false; }

        $cmd("RCPT TO:<{$to}>");
        if (!$ok('250'))                          { fclose($sock); return false; }

        $cmd('DATA');
        if (!$ok('354'))                          { fclose($sock); return false; }

        // Dot-stuffing: lines starting with '.' get an extra '.' prepended
        $lines    = explode("\n", str_replace("\r\n", "\n", $raw));
        $stuffed  = implode("\r\n", array_map(
            fn($l) => str_starts_with($l, '.') ? '.' . $l : $l,
            $lines
        ));
        fwrite($sock, $stuffed . "\r\n.\r\n");

        if (!$ok('250'))                          { fclose($sock); return false; }

        $cmd('QUIT');
        fclose($sock);
        return true;
    }
}
