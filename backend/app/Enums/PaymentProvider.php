<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentProvider: int
{
    case LAVA_TOP = 1;
    case MANUAL = 2;

    public function alias(): string
    {
        return match ($this) {
            self::LAVA_TOP => 'lavatop',
            self::MANUAL => 'manual',
        };
    }

    public static function fromAlias(string $alias): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->alias() === strtolower($alias)) {
                return $case;
            }
        }
        
        return null;
    }
}
