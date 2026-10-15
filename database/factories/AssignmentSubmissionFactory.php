<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssignmentSubmission> */
class AssignmentSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'user_id' => User::factory()->student(),
            'body' => fake()->paragraph(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ];
    }
}
