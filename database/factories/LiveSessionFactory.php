<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LiveSession> */
class LiveSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'trainer_id' => User::factory()->trainer(),
            'title' => fake()->sentence(3),
            'starts_at' => now()->addDay(),
            'duration_minutes' => 60,
            'provider' => 'zoom',
            'meeting_url' => 'https://zoom.us/j/'.fake()->numerify('##########'),
            'status' => 'scheduled',
        ];
    }
}
