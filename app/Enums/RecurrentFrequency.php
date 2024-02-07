<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurrentFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function interval(): string
    {
        return match ($this) {
            self::DAILY => 'days',
            self::WEEKLY => 'weeks',
            self::MONTHLY => 'months',
            self::YEARLY => 'years',
        };
    }

    public static function values(): array
    {
        return array_map(function (RecurrentFrequency $frequency) {
            return $frequency->value;
        }, self::cases());
    }
}
