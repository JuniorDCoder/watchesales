<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum WatchStatus: string
{
    use HasEnumOptions;

    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::Sold => 'Sold',
        };
    }

    /**
     * The schema.org availability URL used in structured data.
     */
    public function schemaAvailability(): string
    {
        return match ($this) {
            self::Available => 'https://schema.org/InStock',
            self::Reserved => 'https://schema.org/LimitedAvailability',
            self::Sold => 'https://schema.org/SoldOut',
        };
    }
}
