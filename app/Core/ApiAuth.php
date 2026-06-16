<?php

namespace App\Core;

use App\Models\ApiKey;

class ApiAuth
{
    private static ?array $currentKey = null;

    public static function authenticate(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            self::deny('Unauthorized', 'UNAUTHORIZED', 401);
        }

        $raw  = substr($header, 7);
        $hash = hash('sha256', $raw);
        $key  = ApiKey::findByHash($hash);

        if (!$key) {
            self::deny('Unauthorized', 'UNAUTHORIZED', 401);
        }

        if ($key['expires_at'] && $key['expires_at'] < date('Y-m-d')) {
            self::deny('API Key abgelaufen', 'KEY_EXPIRED', 401);
        }

        ApiKey::updateLastUsed((int)$key['id']);
        self::$currentKey = $key;
    }

    public static function requireRead(): void
    {
        if (!(int)(self::$currentKey['can_read'] ?? 0)) {
            self::deny('Keine Leseberechtigung', 'FORBIDDEN', 403);
        }
    }

    public static function requireWrite(): void
    {
        if (!(int)(self::$currentKey['can_write'] ?? 0)) {
            self::deny('Keine Schreibberechtigung', 'FORBIDDEN', 403);
        }
    }

    public static function userId(): int
    {
        return (int)(self::$currentKey['user_id'] ?? 0);
    }

    private static function deny(string $message, string $code, int $status): never
    {
        Response::json([
            'success' => false,
            'error'   => $message,
            'code'    => $code,
        ], $status);
    }
}
