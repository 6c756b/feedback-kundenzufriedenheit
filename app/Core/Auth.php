<?php

namespace App\Core;

class Auth
{
    private const SESSION_KEY = 'auth_user';
    private const ROLE_LEVELS = ['none' => 0, 'reader' => 1, 'staff' => 2, 'admin' => 3, 'superadmin' => 4];

    public static function attempt(string $email, string $password): bool
    {
        $config = require ROOT . '/config.php';

        if (($config['env'] ?? 'production') === 'local') {
            return self::attemptLocal($email, $password, $config);
        }

        $db   = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT id, name, email, role, active, password_hash, login_method FROM users WHERE email = ?',
            [$email]
        );

        // Unbekannter Benutzer: LDAP-Bind versuchen, Konto auto-anlegen
        if (!$user) {
            return self::attemptLdapAutoCreate($email, $password, $config);
        }

        $method = $user['login_method'] ?? 'ldap';

        if ($method === 'local') {
            return self::verifyLocalPassword($user, $password);
        }

        $ldapOk = self::performLdapBind($email, $password, $config, $ldapError);

        if ($ldapOk) {
            return self::createSession($user);
        }

        if ($method === 'both') {
            return self::verifyLocalPassword($user, $password);
        }

        // method === 'ldap', kein Fallback
        Session::flash('login_error', 'Anmeldung fehlgeschlagen.' . ($ldapError ? ' (' . $ldapError . ')' : ''));
        return false;
    }

    private static function performLdapBind(string $email, string $password, array $config, ?string &$error = null): bool
    {
        $ldap = $config['ldap'] ?? [];

        if (empty($ldap['host'])) {
            $error = 'LDAP nicht konfiguriert.';
            return false;
        }

        $host   = rtrim($ldap['host'], '/');
        $port   = (int)($ldap['port'] ?? 389);
        $scheme = ($port === 636) ? 'ldaps' : 'ldap';

        if (!str_starts_with($host, 'ldap://') && !str_starts_with($host, 'ldaps://')) {
            $host = $scheme . '://' . $host;
        }

        try {
            $conn = \ldap_connect($host . ':' . $port);
            if (!$conn) {
                $error = 'LDAP-Verbindung fehlgeschlagen.';
                return false;
            }

            \ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            \ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

            if (!@\ldap_bind($conn, $email, $password)) {
                $error = \ldap_error($conn);
                error_log('KZB LDAP bind failed for ' . $email . ': ' . $error);
                \ldap_unbind($conn);
                return false;
            }

            \ldap_unbind($conn);
            return true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            error_log('KZB LDAP error: ' . $e->getMessage());
            return false;
        }
    }

    private static function attemptLdapAutoCreate(string $email, string $password, array $config): bool
    {
        $ldap = $config['ldap'] ?? [];

        $conn = null;
        if (!empty($ldap['host'])) {
            $host   = rtrim($ldap['host'], '/');
            $port   = (int)($ldap['port'] ?? 389);
            $scheme = ($port === 636) ? 'ldaps' : 'ldap';
            if (!str_starts_with($host, 'ldap://') && !str_starts_with($host, 'ldaps://')) {
                $host = $scheme . '://' . $host;
            }

            try {
                $conn = \ldap_connect($host . ':' . $port);
                if ($conn) {
                    \ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                    \ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

                    if (!@\ldap_bind($conn, $email, $password)) {
                        error_log('KZB LDAP bind failed for unknown user ' . $email);
                        \ldap_unbind($conn);
                        Session::flash('login_error', 'Anmeldung fehlgeschlagen.');
                        return false;
                    }
                }
            } catch (\Exception $e) {
                error_log('KZB LDAP error: ' . $e->getMessage());
                Session::flash('login_error', 'Anmeldung fehlgeschlagen.');
                return false;
            }
        } else {
            Session::flash('login_error', 'Anmeldung fehlgeschlagen.');
            return false;
        }

        $ldapInfo = $conn ? self::getLdapUserInfo($conn, $ldap, $email) : [];
        if ($conn) {
            \ldap_unbind($conn);
        }

        $db = Database::getInstance();
        try {
            $db->insert('users', [
                'name'         => $ldapInfo['display_name'] ?? $email,
                'email'        => $email,
                'role'         => 'none',
                'active'       => 1,
                'login_method' => 'ldap',
                'display_name' => $ldapInfo['display_name'] ?? '',
                'job_title'    => $ldapInfo['job_title']    ?? '',
                'phone'        => $ldapInfo['phone']        ?? '',
            ]);
            error_log('KZB: Auto-created user account for ' . $email . ' with role=none');
        } catch (\Exception $e) {
            error_log('KZB: Failed to auto-create user for ' . $email . ': ' . $e->getMessage());
        }

        Session::flash('login_error', 'Benutzerkonto nicht aktiviert, bitte an die Administration wenden.');
        return false;
    }

    private static function verifyLocalPassword(array $user, string $password): bool
    {
        if (empty($user['password_hash'])) {
            Session::flash('login_error', 'Anmeldung fehlgeschlagen.');
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            Session::flash('login_error', 'Anmeldung fehlgeschlagen.');
            return false;
        }

        if ($user['role'] === 'none' || !$user['active']) {
            Session::flash('login_error', 'Benutzerkonto nicht aktiviert, bitte an die Administration wenden.');
            return false;
        }

        return self::createSession($user);
    }

    private static function createSession(array $user): bool
    {
        if ($user['role'] === 'none' || !$user['active']) {
            Session::flash('login_error', 'Benutzerkonto nicht aktiviert, bitte an die Administration wenden.');
            return false;
        }

        session_regenerate_id(true);
        Csrf::regenerate();

        Session::set(self::SESSION_KEY, [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ]);

        return true;
    }

    private static function attemptLocalPassword(string $email, string $password): bool
    {
        $db   = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT id, name, email, role, active, password_hash FROM users WHERE email = ?',
            [$email]
        );

        if (!$user) {
            Session::flash('login_error', 'Anmeldung fehlgeschlagen.');
            return false;
        }

        return self::verifyLocalPassword($user, $password);
    }

    private static function attemptLocal(string $email, string $password, array $config): bool
    {
        $devUsers = $config['dev_auth']['users'] ?? [];
        $devUser  = null;

        foreach ($devUsers as $u) {
            if ($u['email'] === $email && $u['password'] === $password) {
                $devUser = $u;
                break;
            }
        }

        if (!$devUser) {
            return self::attemptLocalPassword($email, $password);
        }

        $db   = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT id, name, email, role, active FROM users WHERE email = ?',
            [$email]
        );

        if (!$user) {
            $db->insert('users', [
                'name'   => $devUser['name'],
                'email'  => $email,
                'role'   => $devUser['role'],
                'active' => 1,
            ]);
            $user = $db->fetchOne(
                'SELECT id, name, email, role, active FROM users WHERE email = ?',
                [$email]
            );
        }

        session_regenerate_id(true);
        Csrf::regenerate();

        Session::set(self::SESSION_KEY, [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ]);

        return true;
    }

    private static function getLdapUserInfo($conn, array $ldap, string $email): array
    {
        $info = ['display_name' => null, 'job_title' => null, 'phone' => null];

        if (empty($ldap['base_dn'])) {
            return $info;
        }

        try {
            $filter  = '(|(mail=' . ldap_escape($email, '', LDAP_ESCAPE_FILTER) . ')' .
                       '(userPrincipalName=' . ldap_escape($email, '', LDAP_ESCAPE_FILTER) . '))';
            $attrs   = ['displayname', 'cn', 'title', 'telephonenumber', 'mobile'];
            $search  = @\ldap_search($conn, $ldap['base_dn'], $filter, $attrs, 0, 1);
            if (!$search) {
                return $info;
            }
            $entries = \ldap_get_entries($conn, $search);
            if ($entries['count'] > 0) {
                $e = $entries[0];
                $info['display_name'] = $e['displayname'][0] ?? $e['cn'][0] ?? null;
                $info['job_title']    = $e['title'][0]           ?? null;
                $info['phone']        = $e['telephonenumber'][0] ?? $e['mobile'][0] ?? null;
            }
        } catch (\Exception $e) {
            // Best-effort, kein Fehler nach oben
        }

        return $info;
    }

    public static function user(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    public static function check(): bool
    {
        return Session::get(self::SESSION_KEY) !== null;
    }

    public static function hasRole(string $minimum): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $userLevel = self::ROLE_LEVELS[$user['role']] ?? 0;
        $minLevel  = self::ROLE_LEVELS[$minimum] ?? 99;

        return $userLevel >= $minLevel;
    }

    public static function logout(): void
    {
        Session::delete(self::SESSION_KEY);
    }
}
