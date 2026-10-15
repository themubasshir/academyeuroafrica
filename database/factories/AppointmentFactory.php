<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trainer_id' => User::factory()->trainer(),
            'student_id' => User::factory()->student(),
            'starts_at' => now()->addDays(3)->setHour(10)->setMinute(0),
            'duration_minutes' => 60,
            'status' => 'booked',
        ];
    }
}
