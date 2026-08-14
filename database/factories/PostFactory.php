<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\ThreadsAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
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
            'threads_media_id' => null,
            'text' => fake()->realText(200),
            'scheduled_at' => now()->addHour(),
            'published_at' => null,
            'status' => PostStatus::Scheduled,
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the post has been published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'threads_media_id' => fake()->numerify('##########'),
            'published_at' => now(),
            'status' => PostStatus::Published,
        ]);
    }

    /**
     * Indicate that the post failed to publish.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Failed,
            'error_message' => 'token 失效',
        ]);
    }
}
