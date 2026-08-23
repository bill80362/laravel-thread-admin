<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
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
            'threads_account_id' => ThreadsAccount::factory(),
            'type' => 'post',
            'reference_id' => null,
            'threads_media_id' => 'media-'.$this->faker->uuid(),
            'text' => $this->faker->sentence(),
        ];
    }

    /**
     * Indicate that the log is a post activity.
     */
    public function post(): static
    {
        return $this->state(['type' => 'post']);
    }

    /**
     * Indicate that the log is a reply activity.
     */
    public function reply(): static
    {
        return $this->state(['type' => 'reply']);
    }
}
