<?php

namespace App\Models;

use App\Core\Database;

class Metropolregion
{
    public static function findAll(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM metropolregionen ORDER BY sort_order, name'
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM metropolregionen WHERE id = ?', [$id]
        );
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO metropolregionen (name, sort_order) VALUES (?, ?)',
            [$data['name'], $data['sort_order']]
        );
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::getInstance()->execute(
            'UPDATE metropolregionen SET name = ?, sort_order = ? WHERE id = ?',
            [$data['name'], $data['sort_order'], $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->execute(
            'DELETE FROM metropolregionen WHERE id = ?', [$id]
        );
    }
}
