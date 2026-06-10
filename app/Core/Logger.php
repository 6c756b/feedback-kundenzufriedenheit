<?php

namespace App\Core;

class Logger
{
    public static function backend(
        string $action,
        string $entityType,
        int    $entityId,
        string $description
    ): void {
        $user   = Auth::user();
        $userId = $user['id'] ?? null;
        $ip     = (new Request())->ip();

        Database::getInstance()->insert('logs', [
            'user_id'     => $userId,
            'actor_type'  => 'backend',
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description,
            'ip_address'  => $ip,
            'survey_code' => null,
        ]);
    }

    public static function frontend(
        string $action,
        string $surveyCode,
        string $description
    ): void {
        $ip = (new Request())->ip();

        Database::getInstance()->insert('logs', [
            'user_id'     => null,
            'actor_type'  => 'frontend',
            'action'      => $action,
            'entity_type' => 'survey',
            'entity_id'   => null,
            'description' => $description,
            'ip_address'  => $ip,
            'survey_code' => $surveyCode,
        ]);
    }
}
