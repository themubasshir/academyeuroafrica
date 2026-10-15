<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Group> */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'trainer_id' => User::factory()->trainer(),
            'name' => fake()->words(2, true),
        ];
    }
}
