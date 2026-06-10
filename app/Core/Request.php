<?php

namespace App\Core;

class Request
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $_SERVER[$key] ?? $default;
    }

    public function method(): string
    {
        $override = $_POST['_method'] ?? null;
        if ($override && in_array(strtoupper($override), ['PUT', 'DELETE', 'PATCH'], true)) {
            return strtoupper($override);
        }
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return '/' . ltrim($path ?? '/', '/');
    }

    public function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    public function isJson(): bool
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($ct, 'application/json');
    }

    public function jsonBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    public function remoteAddr(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function ip(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
