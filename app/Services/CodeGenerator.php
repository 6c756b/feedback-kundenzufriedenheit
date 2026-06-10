<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

class CodeGenerator
{
    private const CONSONANTS = 'bcdfghjklmnpqrstvwxyz';
    private const VOWELS     = 'aeiou';
    private const DIGITS     = '0123456789';
    private const MAX_TRIES  = 10;

    public static function generate(): string
    {
        for ($i = 0; $i < self::MAX_TRIES; $i++) {
            $code = self::buildCode();
            if (!self::exists($code)) {
                return $code;
            }
        }

        Logger::backend('code.exhausted', 'survey', 0, 'Kein freier Code nach ' . self::MAX_TRIES . ' Versuchen');
        throw new RuntimeException('Kein freier Befragungscode generierbar.');
    }

    private static function buildCode(): string
    {
        $c = self::CONSONANTS;
        $v = self::VOWELS;
        $d = self::DIGITS;

        return
            $c[random_int(0, strlen($c) - 1)] .
            $v[random_int(0, strlen($v) - 1)] .
            $c[random_int(0, strlen($c) - 1)] .
            $v[random_int(0, strlen($v) - 1)] .
            $c[random_int(0, strlen($c) - 1)] .
            $v[random_int(0, strlen($v) - 1)] .
            $d[random_int(0, strlen($d) - 1)] .
            $d[random_int(0, strlen($d) - 1)];
    }

    private static function exists(string $code): bool
    {
        return Database::getInstance()->fetchOne(
            'SELECT id FROM surveys WHERE code = ?', [$code]
        ) !== null;
    }
}
