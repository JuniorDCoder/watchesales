<?php

namespace Database\Factories;

use App\Models\Watch;
use App\Models\WatchImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WatchImage>
 */
class WatchImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'watch_id' => Watch::factory(),
            'path' => 'watches/'.fake()->uuid().'.jpg',
            'alt' => fake()->sentence(4),
            'sort_order' => 0,
        ];
    }
}
