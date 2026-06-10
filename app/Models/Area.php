<?php

namespace App\Models;

use App\Core\Database;

class Area
{
    public static function findAll(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM areas ORDER BY sort_order, name'
        );
    }

    public static function findAllWithStats(): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT a.*,
                COALESCE(q.active_count,   0) AS q_active,
                COALESCE(q.inactive_count, 0) AS q_inactive,
                COALESCE(s.open_count,       0) AS s_open,
                COALESCE(s.evaluation_count, 0) AS s_evaluation,
                COALESCE(s.archived_count,   0) AS s_archived
            FROM areas a
            LEFT JOIN (
                SELECT area_id,
                    SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) AS inactive_count
                FROM questions
                GROUP BY area_id
            ) q ON q.area_id = a.id
            LEFT JOIN (
                SELECT sa.area_id,
                    SUM(CASE WHEN s.status IN ('open','started')  THEN 1 ELSE 0 END) AS open_count,
                    SUM(CASE WHEN s.status = 'completed'          THEN 1 ELSE 0 END) AS evaluation_count,
                    SUM(CASE WHEN s.status = 'archived'           THEN 1 ELSE 0 END) AS archived_count
                FROM survey_areas sa
                JOIN surveys s ON s.id = sa.survey_id
                GROUP BY sa.area_id
            ) s ON s.area_id = a.id
            ORDER BY a.sort_order, a.name"
        );
    }

    public static function findAllActive(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM areas WHERE active = 1 ORDER BY sort_order, name'
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM areas WHERE id = ?', [$id]
        );
    }

    public static function create(array $data): int
    {
        return Database::getInstance()->insert('areas', $data);
    }

    public static function update(int $id, array $data): void
    {
        $sets   = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        Database::getInstance()->execute("UPDATE areas SET $sets WHERE id = ?", $params);
    }

    public static function canDelete(int $id): bool
    {
        // Allgemein (id=1) ist nicht löschbar
        if ($id === 1) {
            return false;
        }

        // Nicht löschbar wenn noch aktive Fragen vorhanden
        $row = Database::getInstance()->fetchOne(
            'SELECT COUNT(*) AS cnt FROM questions WHERE area_id = ?', [$id]
        );
        return ($row['cnt'] ?? 0) == 0;
    }
}
