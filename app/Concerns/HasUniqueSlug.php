<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates a unique, URL friendly slug when a model is saved without one.
 *
 * @mixin Model
 */
trait HasUniqueSlug
{
    protected static function bootHasUniqueSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            $slug = Str::slug((string) ($model->slug ?: $model->slugSource()));

            $model->slug = $model->uniqueSlug($slug !== '' ? $slug : Str::lower(Str::random(8)));
        });
    }

    /**
     * The text the slug is derived from when none was provided.
     */
    protected function slugSource(): string
    {
        return (string) $this->name;
    }

    /**
     * Append an incrementing suffix until the slug is unused by other records.
     */
    protected function uniqueSlug(string $slug): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (static::query()
            ->where('slug', $candidate)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
