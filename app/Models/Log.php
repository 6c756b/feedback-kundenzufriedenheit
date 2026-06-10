<?php

namespace App\Models;

use App\Core\Database;

class Log
{
    public static function findAll(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        [$where, $params] = self::buildWhereClause($filters);
        $offset   = ($page - 1) * $perPage;
        $params[] = $perPage;
        $params[] = $offset;

        return Database::getInstance()->fetchAll(
            "SELECT l.*, u.name AS user_name, s.customer_name AS survey_customer_name
             FROM logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN surveys s ON l.actor_type = 'frontend' AND s.code = l.survey_code
             $where
             ORDER BY l.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function countAll(array $filters = []): int
    {
        [$where, $params] = self::buildWhereClause($filters);

        $row = Database::getInstance()->fetchOne(
            "SELECT COUNT(*) AS cnt FROM logs l $where",
            $params
        );
        return (int) ($row['cnt'] ?? 0);
    }

    private static function buildWhereClause(array $filters): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['actor_type'])) {
            $conditions[] = 'l.actor_type = ?';
            $params[]     = $filters['actor_type'];
        }

        if (!empty($filters['action'])) {
            $conditions[] = 'l.action = ?';
            $params[]     = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(l.created_at) >= ?';
            $params[]     = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(l.created_at) <= ?';
            $params[]     = $filters['date_to'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }
}
