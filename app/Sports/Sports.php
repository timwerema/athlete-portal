<?php

namespace App\Sports;

use InvalidArgumentException;

class Sports
{
    /** @var array<string, class-string<Sport>> */
    private const REGISTRY = [
        'soccer' => Soccer::class,
    ];

    public static function get(string $key): Sport
    {
        $class = self::REGISTRY[$key] ?? throw new InvalidArgumentException("Unknown sport [$key].");

        return new $class;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::REGISTRY);
    }
}
