<?php

namespace App\Core;

class Session
{
    public static function start(int $lifetimeMinutes = 480): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = $lifetimeMinutes * 60;

        // Eigenes Verzeichnis außerhalb von /var/lib/php/sessions -
        // verhindert dass der Debian/Ubuntu-Systemcron (der System-php.ini liest,
        // typisch gc_maxlifetime=1440s) die Sessions vorzeitig wegräumt.
        $savePath = ROOT . '/storage/sessions';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0700, true);
        }
        session_save_path($savePath);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.gc_maxlifetime', $lifetime);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
