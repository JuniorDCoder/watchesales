<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum WatchCondition: string
{
    use HasEnumOptions;

    case New = 'new';
    case Unworn = 'unworn';
    case PreOwned = 'pre_owned';
    case Vintage = 'vintage';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Unworn => 'Unworn',
            self::PreOwned => 'Pre-owned',
            self::Vintage => 'Vintage',
        };
    }

    /**
     * The schema.org item condition URL used in structured data.
     */
    public function schemaCondition(): string
    {
        return match ($this) {
            self::New, self::Unworn => 'https://schema.org/NewCondition',
            self::PreOwned, self::Vintage => 'https://schema.org/UsedCondition',
        };
    }
}
