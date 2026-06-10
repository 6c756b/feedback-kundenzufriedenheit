<?php

namespace App\Core;

class Response
{
    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function setHeader(string $name, string $value): void
    {
        header($name . ': ' . $value);
    }

    public static function notFound(): never
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="de"><body><h1>404 - Seite nicht gefunden</h1></body></html>';
        exit;
    }

    public static function forbidden(): never
    {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="de"><body><h1>403 - Zugriff verweigert</h1></body></html>';
        exit;
    }
}
