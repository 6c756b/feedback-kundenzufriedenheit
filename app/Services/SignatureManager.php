<?php

namespace App\Services;

class SignatureManager
{
    public static function emailToSlug(string $email): string
    {
        return str_replace(['@', '.'], ['_at_', '_dot_'], strtolower(trim($email)));
    }

    public static function slugToEmail(string $slug): string
    {
        $s = str_replace('_dot_', '.', $slug);
        return str_replace('_at_', '@', $s);
    }

    public static function slugIsValid(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9_]+$/', $slug);
    }

    public static function render(array $user): string
    {
        $config       = require ROOT . '/config.php';
        $templateFile = $config['templates']['signature'] ?? 'signature.html';
        $templatePath = ROOT . '/resources/templates/' . $templateFile;
        if (!file_exists($templatePath)) {
            return '';
        }

        $html     = (string) file_get_contents($templatePath);
        $email    = (string)($user['email'] ?? '');
        $username = strstr($email, '@', true) ?: $email;

        // {{#if_image}}...{{/if_image}} block: keep content with image, or remove entire block
        if (!empty($user['signature_image'])) {
            $html = str_replace(['{{#if_image}}', '{{/if_image}}'], '', $html);
            $html = str_replace('{{image_base64}}', $user['signature_image'], $html);
        } else {
            $html = (string) preg_replace('/\{\{#if_image\}\}.*?\{\{\/if_image\}\}/s', '', $html);
        }

        $map = [
            '{{display_name}}' => htmlspecialchars((string)($user['display_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            '{{job_title}}'    => htmlspecialchars((string)($user['job_title']    ?? ''), ENT_QUOTES, 'UTF-8'),
            '{{phone}}'        => htmlspecialchars((string)($user['phone']        ?? ''), ENT_QUOTES, 'UTF-8'),
            '{{email}}'        => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
            '{{username}}'     => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
        ];

        return str_replace(array_keys($map), array_values($map), $html);
    }

    public static function forEmail(string $email): ?string
    {
        if ($email === '') {
            return null;
        }
        return self::emailToSlug($email);
    }
}
