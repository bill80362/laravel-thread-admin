<?php

namespace Database\Factories;

use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThreadsAccount>
 */
class ThreadsAccountFactory extends Factory
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
            'threads_user_id' => fake()->unique()->numerify('##########'),
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'avatar' => fake()->imageUrl(),
            'access_token' => 'test-access-token',
            'token_expires_at' => now()->addDays(60),
            'status' => ThreadsAccountStatus::Active,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the account needs reauthorization.
     */
    public function needsReauth(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThreadsAccountStatus::NeedsReauth,
        ]);
    }
}
