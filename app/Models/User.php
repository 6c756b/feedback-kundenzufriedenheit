<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findAll(string $sortBy = 'name', string $sortDir = 'ASC'): array
    {
        $allowed = ['name', 'email', 'role', 'active', 'is_sales', 'is_projectlead'];
        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = 'name';
        }
        $sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';
        return Database::getInstance()->fetchAll(
            "SELECT * FROM users ORDER BY `$sortBy` $sortDir"
        );
    }

    public static function findSales(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM users WHERE is_sales = 1 AND active = 1 ORDER BY name'
        );
    }

    public static function findProjectLeads(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM users WHERE is_projectlead = 1 AND active = 1 ORDER BY name'
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM users WHERE id = ?', [$id]
        );
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM users WHERE email = ?', [$email]
        );
    }

    public static function findSalesByName(string $name): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM users WHERE name = ? AND is_sales = 1 AND active = 1',
            [$name]
        );
    }

    public static function findProjectLeadByName(string $name): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM users WHERE name = ? AND is_projectlead = 1 AND active = 1',
            [$name]
        );
    }

    public static function findByName(string $name): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM users WHERE name = ? AND active = 1',
            [$name]
        );
    }

    public static function create(array $data): int
    {
        return Database::getInstance()->insert('users', $data);
    }

    public static function update(int $id, array $data): void
    {
        $sets   = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        Database::getInstance()->execute("UPDATE users SET $sets WHERE id = ?", $params);
    }
}
