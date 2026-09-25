<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum WatchGender: string
{
    use HasEnumOptions;

    case Men = 'men';
    case Women = 'women';
    case Unisex = 'unisex';

    public function label(): string
    {
        return match ($this) {
            self::Men => 'Men',
            self::Women => 'Women',
            self::Unisex => 'Unisex',
        };
    }
}
