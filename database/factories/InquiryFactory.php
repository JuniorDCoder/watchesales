<?php

namespace Database\Factories;

use App\Enums\InquiryChannel;
use App\Models\Inquiry;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
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
            'channel' => fake()->randomElement(InquiryChannel::cases()),
        ];
    }
}
