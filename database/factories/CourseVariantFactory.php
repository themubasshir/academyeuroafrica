<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseVariant> */
class CourseVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'name' => 'Standard',
            'delivery_type' => 'live',
            'price_cents' => fake()->numberBetween(5000, 50000),
            'currency' => 'EUR',
            'status' => 'published',
            'approval_status' => 'approved', // factory represents a sellable variant
        ];
    }
}
