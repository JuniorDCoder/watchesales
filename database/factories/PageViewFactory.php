<?php

namespace Database\Factories;

use App\Models\PageView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageView>
 */
class PageViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'watch_id' => null,
            'path' => '/',
            'visitor_hash' => hash('sha256', fake()->uuid()),
            'source' => 'Direct',
            'referrer_host' => null,
            'device' => 'desktop',
        ];
    }
}
