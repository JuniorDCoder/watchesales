<?php

namespace Database\Factories;

use App\Models\SearchQuery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchQuery>
 */
class SearchQueryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'term' => fake()->word(),
            'results_count' => fake()->numberBetween(0, 5),
            'visitor_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
