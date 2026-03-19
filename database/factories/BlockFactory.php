<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'type' => 'text',
            'props' => ['content' => fake()->sentence()],
            'order' => 0,
            'is_active' => true,
            'is_published' => false,
        ];
    }
}
