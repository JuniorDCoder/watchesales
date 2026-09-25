<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum WatchMovement: string
{
    use HasEnumOptions;

    case Automatic = 'automatic';
    case ManualWind = 'manual_wind';
    case Quartz = 'quartz';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automatic',
            self::ManualWind => 'Manual wind',
            self::Quartz => 'Quartz',
        };
    }
}
