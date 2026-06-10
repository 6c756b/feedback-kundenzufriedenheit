<?php

namespace App\Services;

class OutlookText
{
    public static function subject(array $survey): string
    {
        $config = require ROOT . '/config.php';
        $label  = $config['app']['survey_label'] ?? 'Befragung';
        return $label . ' - ' . $survey['project_name'];
    }

    public static function htmlBody(array $survey, string $senderName = ''): string
    {
        $vars = self::buildVars($survey, $senderName);
        $config       = require ROOT . '/config.php';
        $templateName = $config['templates']['email'] ?? 'email.html';
        return self::renderHtml(self::loadTemplate($templateName), $vars);
    }

    private static function buildVars(array $survey, string $senderName): array
    {
        $config      = require ROOT . '/config.php';
        $baseUrl     = rtrim($config['app']['url'], '/');
        $companyName = $config['app']['company_name'];

        $contactPerson = $survey['contact_person'];
        $salutation = match ($survey['contact_salutation'] ?? '') {
            'herr'  => "Sehr geehrter Herr {$contactPerson}",
            'frau'  => "Sehr geehrte Frau {$contactPerson}",
            default => "Sehr geehrte/r {$contactPerson}",
        };

        return [
            'salutation'   => $salutation,
            'project_name' => $survey['project_name'],
            'company_name' => $companyName,
            'survey_url'   => $baseUrl . '/?code=' . $survey['code'],
            'survey_code'  => $survey['code'],
            'sender_name'  => $senderName ?: $companyName,
        ];
    }

    private static function loadTemplate(string $name): string
    {
        $path = ROOT . '/resources/templates/' . $name;
        if (!file_exists($path)) {
            throw new \RuntimeException("E-Mail-Template nicht gefunden: {$name}");
        }
        return file_get_contents($path);
    }

    private static function renderHtml(string $template, array $vars): string
    {
        $escaped = array_map(fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $vars);
        $search  = array_map(fn($k) => '{{' . $k . '}}', array_keys($vars));
        return str_replace($search, $escaped, $template);
    }
}
