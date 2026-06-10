<?php

namespace App\Core;

class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(): bool
    {
        $token    = $_POST['csrf_token'] ?? '';
        $expected = $_SESSION[self::SESSION_KEY] ?? '';

        if (empty($token) || empty($expected)) {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function validateHeader(): bool
    {
        $token    = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION[self::SESSION_KEY] ?? '';

        if (empty($token) || empty($expected)) {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function regenerate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}
