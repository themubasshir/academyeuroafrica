<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Programme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(4, true);

        return [
            'programme_id' => Programme::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'delivery_types' => ['live'],
            'status' => 'published',
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }
}
