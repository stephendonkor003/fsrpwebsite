<?php

namespace Database\Factories;

use App\Models\EventResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventResource>
 */
class EventResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ['en' => fake()->sentence(4)],
            'description' => ['en' => fake()->paragraph()],
            'category' => 'programme',
            'language' => 'en',
            'file_path' => 'event-resources/'.fake()->uuid().'.pdf',
            'original_filename' => 'programme-outline.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
