<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quiz> */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'creator_id' => User::factory()->trainer(),
            'title' => fake()->sentence(3),
            'max_attempts' => 1,
            'release_results' => 'after_close',
            'status' => 'published',
        ];
    }
}
