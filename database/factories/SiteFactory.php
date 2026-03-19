<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'name' => Str::limit($name, 100, ''),
            'slug' => fake()->unique()->slug(),
            'bio' => fake()->optional()->paragraph(),
            'avatar_url' => fake()->optional()->url(),
            'published_at' => null,
        ];
    }
}
