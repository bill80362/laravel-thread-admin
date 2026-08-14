<?php

namespace Database\Factories;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reply>
 */
class ReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'threads_account_id' => ThreadsAccount::factory(),
            'post_id' => null,
            'threads_reply_id' => fake()->unique()->numerify('##########'),
            'author_username' => fake()->userName(),
            'text' => fake()->sentence(),
            'source' => ReplySource::Polling,
            'status' => ReplyStatus::New,
            'replied_at' => null,
        ];
    }

    /**
     * Indicate that the reply has been answered.
     */
    public function replied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReplyStatus::Replied,
            'replied_at' => now(),
        ]);
    }

    /**
     * Indicate that the reply has been ignored.
     */
    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReplyStatus::Ignored,
        ]);
    }
}
