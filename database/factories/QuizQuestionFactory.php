<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuizQuestion> */
class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => 'mcq',
            'prompt' => fake()->sentence().'?',
            'points' => 1,
        ];
    }
}
