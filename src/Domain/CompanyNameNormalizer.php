<?php

declare(strict_types=1);

namespace App\Domain;

final class CompanyNameNormalizer
{
    public static function displayName(string $name): string
    {
        $name = trim($name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }

    public static function key(string $name): string
    {
        return mb_strtolower(self::displayName($name));
    }

    public static function searchPattern(string $query): string
    {
        return '%'.str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            self::key($query),
        ).'%';
    }
}
