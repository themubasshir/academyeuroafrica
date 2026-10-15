<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assignment> */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'creator_id' => User::factory()->trainer(),
            'title' => fake()->sentence(4),
            'audience' => 'course',
            'max_points' => 100,
            'status' => 'published',
        ];
    }
}
