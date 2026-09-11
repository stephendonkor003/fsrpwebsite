<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => ['en' => fake()->sentence(4)],
            'excerpt' => ['en' => fake()->sentence()],
            'body' => ['en' => fake()->paragraph()],
            'venue' => ['en' => fake()->city()],
            'start_at' => now()->addWeek(),
            'end_at' => now()->addWeek()->addDay(),
            'mode' => 'in-person',
            'is_published' => true,
            'is_featured' => false,
        ];
    }
}
