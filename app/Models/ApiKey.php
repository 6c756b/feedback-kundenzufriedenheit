<?php

namespace App\Models;

use App\Core\Database;

class ApiKey
{
    public static function findByHash(string $hash): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM api_keys WHERE key_hash = ? AND active = 1',
            [$hash]
        );
    }

    public static function updateLastUsed(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE api_keys SET last_used_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $id]
        );
    }

    public static function create(string $name, string $keyHash, int $userId, int $canRead, int $canWrite, ?string $expiresAt): int
    {
        return Database::getInstance()->insert('api_keys', [
            'name'       => $name,
            'key_hash'   => $keyHash,
            'user_id'    => $userId,
            'can_read'   => $canRead,
            'can_write'  => $canWrite,
            'expires_at' => $expiresAt,
        ]);
    }

    public static function findAll(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT id, name, can_read, can_write, expires_at, active, last_used_at, created_at FROM api_keys ORDER BY created_at DESC'
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM api_keys WHERE id = ?', [$id]
        );
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->execute('DELETE FROM api_keys WHERE id = ?', [$id]);
    }
}
