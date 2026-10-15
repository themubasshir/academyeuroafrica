<?php

namespace Database\Factories;

use App\Models\CourseVariant;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrolment> */
class EnrolmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'course_variant_id' => CourseVariant::factory(),
            'status' => 'active',
            'enrolled_at' => now(),
        ];
    }
}
