<?php

namespace App\Controllers\Backend;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;

class AuthController
{
    private const MAX_ATTEMPTS  = 5;
    private const WINDOW_MINUTES = 10;

    public function showLogin(array $params = []): void
    {
        if (Auth::check()) {
            Response::redirect('/backend');
        }

        $pageTitle = 'Anmelden';
        $error     = Session::flash('login_error');
        ob_start();
        require ROOT . '/app/Views/backend/login.php';
        $content = ob_get_clean();
        require ROOT . '/app/Views/layout/backend-auth.php';
    }

    public function login(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::forbidden();
        }

        $request = new Request();
        $ip      = $request->remoteAddr();
        $email   = trim($request->post('email', ''));
        $password = $request->post('password', '');

        if ($this->isRateLimited($ip)) {
            Session::flash('login_error', 'Zu viele Fehlversuche. Bitte warten Sie 10 Minuten.');
            Response::redirect('/backend/login');
        }

        if (Auth::attempt($email, $password)) {
            $user = Auth::user();
            Logger::backend('auth.login', 'user', (int)$user['id'], 'Login erfolgreich');
            Response::redirect('/backend');
        }

        $this->recordFailedAttempt($ip);
        Logger::backend('auth.failed', 'user', 0, 'Login fehlgeschlagen für: ' . $email);
        if (!isset($_SESSION['_flash']['login_error'])) {
            Session::flash('login_error', 'E-Mail oder Passwort ungültig, oder kein Zugriff.');
        }
        Response::redirect('/backend/login');
    }

    public function logout(array $params = []): void
    {
        if (!Csrf::validate()) {
            Response::redirect('/backend');
        }

        if (Auth::check()) {
            $user = Auth::user();
            Logger::backend('auth.logout', 'user', (int)$user['id'], 'Logout');
        }
        Auth::logout();
        Session::destroy();
        Response::redirect('/backend/login');
    }

    private function isRateLimited(string $ip): bool
    {
        $window = date('Y-m-d H:i:s', strtotime('-' . self::WINDOW_MINUTES . ' minutes'));
        $row    = Database::getInstance()->fetchOne(
            'SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?',
            [$ip, $window]
        );
        return (int)($row['cnt'] ?? 0) >= self::MAX_ATTEMPTS;
    }

    private function recordFailedAttempt(string $ip): void
    {
        Database::getInstance()->insert('login_attempts', ['ip_address' => $ip]);

        // Alte Einträge bereinigen
        $old = date('Y-m-d H:i:s', strtotime('-1 hour'));
        Database::getInstance()->execute(
            'DELETE FROM login_attempts WHERE attempted_at < ?', [$old]
        );
    }
}
