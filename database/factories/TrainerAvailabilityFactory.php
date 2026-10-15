<?php

namespace Database\Factories;

use App\Models\TrainerAvailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TrainerAvailability> */
class TrainerAvailabilityFactory extends Factory
{
    protected $model = TrainerAvailability::class;

    public function definition(): array
    {
        $start = now()->addDays(2)->setHour(9)->setMinute(0);

        return [
            'trainer_id' => User::factory()->trainer(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(4),
        ];
    }
}
