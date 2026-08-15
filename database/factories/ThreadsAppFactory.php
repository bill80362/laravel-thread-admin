<?php

namespace Database\Factories;

use App\Models\ThreadsApp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThreadsApp>
 */
class ThreadsAppFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'client_id' => (string) fake()->unique()->numerify('##############'),
            'client_secret' => fake()->sha256(),
        ];
    }
}
