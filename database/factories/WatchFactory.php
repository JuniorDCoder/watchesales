<?php

namespace Database\Factories;

use App\Enums\WatchCondition;
use App\Enums\WatchGender;
use App\Enums\WatchMovement;
use App\Enums\WatchStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Watch>
 */
class WatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'name' => ucwords(fake()->unique()->word().' '.fake()->word()),
            'reference' => strtoupper(fake()->bothify('###??-####')),
            'summary' => fake()->sentence(14),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->randomFloat(2, 500, 40000),
            'status' => WatchStatus::Available,
            'condition' => fake()->randomElement(WatchCondition::cases()),
            'movement' => fake()->randomElement(WatchMovement::cases()),
            'gender' => WatchGender::Unisex,
            'year' => fake()->numberBetween(1960, 2026),
            'case_material' => 'Stainless steel',
            'case_diameter' => fake()->randomFloat(1, 34, 44),
            'water_resistance' => fake()->randomElement([30, 50, 100, 300]),
            'dial_color' => fake()->safeColorName(),
            'strap_material' => 'Leather',
            'has_box' => fake()->boolean(),
            'has_papers' => fake()->boolean(),
            'is_featured' => false,
            'is_published' => true,
        ];
    }

    /**
     * Indicate that the watch is highlighted on the home page.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the watch is hidden from the storefront.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    /**
     * Indicate that the watch has been sold.
     */
    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WatchStatus::Sold,
        ]);
    }
}
