<?php

namespace App\Concerns;

/**
 * Exposes a backed enum's cases as value and label pairs for select inputs.
 */
trait HasEnumOptions
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
