<?php

namespace App\Models;

use App\Core\Database;

class Question
{
    public static function findAll(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT q.*, a.name AS area_name, a.active AS area_active
             FROM questions q
             JOIN areas a ON a.id = q.area_id
             ORDER BY a.sort_order, a.name, q.sequence, q.id'
        );
    }

    public static function findByArea(int $areaId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM questions WHERE area_id = ? ORDER BY sequence, id',
            [$areaId]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT q.*, a.name AS area_name
             FROM questions q
             JOIN areas a ON a.id = q.area_id
             WHERE q.id = ?',
            [$id]
        );
    }

    /** Lädt alle aktiven Fragen für die gewählten Bereiche einer Befragung. */
    public static function findForSurvey(array $areaIds): array
    {
        if (empty($areaIds)) {
            return [];
        }

        $ids          = array_unique(array_map('intval', $areaIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return Database::getInstance()->fetchAll(
            "SELECT q.*, a.name AS area_name
             FROM questions q
             JOIN areas a ON a.id = q.area_id
             WHERE q.active = 1 AND q.area_id IN ($placeholders)
             ORDER BY a.sort_order, a.name, q.sequence, q.id",
            $ids
        );
    }

    public static function create(array $data): int
    {
        return Database::getInstance()->insert('questions', $data);
    }

    public static function update(int $id, array $data): void
    {
        $sets   = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        Database::getInstance()->execute("UPDATE questions SET $sets WHERE id = ?", $params);
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->execute('DELETE FROM questions WHERE id = ?', [$id]);
    }

    public static function updateSequence(array $ids): void
    {
        $db   = Database::getInstance();
        $stmt = 'UPDATE questions SET sequence = ? WHERE id = ?';
        foreach ($ids as $seq => $id) {
            $db->execute($stmt, [(int)$seq + 1, (int)$id]);
        }
    }
}
