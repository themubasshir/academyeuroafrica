<?php

namespace Tests\Feature\Scheduling;

use App\Domain\Scheduling\BookingService;
use App\Models\SessionCreditLedger;
use App\Models\TrainerAvailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingService;
    }

    private function trainerWithSlot(): array
    {
        $trainer = User::factory()->trainer()->create();
        $start = now()->addDays(5)->setHour(10)->setMinute(0)->setSecond(0);
        TrainerAvailability::create([
            'trainer_id' => $trainer->id,
            'starts_at' => $start->copy()->subHour(),
            'ends_at' => $start->copy()->addHours(3),
        ]);

        return [$trainer, $start];
    }

    public function test_student_with_credit_books_slot(): void
    {
        [$trainer, $start] = $this->trainerWithSlot();
        $student = User::factory()->student()->create();
        $this->service->grantCredits($student, 1);

        $appt = $this->service->book($student, $trainer, $start);

        $this->assertSame('booked', $appt->status);
        $this->assertSame(0, SessionCreditLedger::balanceFor($student->id));
    }

    public function test_booking_without_credit_fails(): void
    {
        [$trainer, $start] = $this->trainerWithSlot();
        $student = User::factory()->student()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->book($student, $trainer, $start);
    }

    public function test_booking_outside_availability_fails(): void
    {
        $trainer = User::factory()->trainer()->create();
        $student = User::factory()->student()->create();
        $this->service->grantCredits($student, 1);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->book($student, $trainer, now()->addDays(9));
    }

    public function test_double_booking_same_slot_fails(): void
    {
        [$trainer, $start] = $this->trainerWithSlot();
        $s1 = User::factory()->student()->create();
        $s2 = User::factory()->student()->create();
        $this->service->grantCredits($s1, 1);
        $this->service->grantCredits($s2, 1);

        $this->service->book($s1, $trainer, $start);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->book($s2, $trainer, $start);
    }

    public function test_cancelled_slot_can_be_rebooked(): void
    {
        [$trainer, $start] = $this->trainerWithSlot();
        $student = User::factory()->student()->create();
        $this->service->grantCredits($student, 2);

        $appt = $this->service->book($student, $trainer, $start);
        $this->service->cancel($appt, $student);

        $appt2 = $this->service->book($student, $trainer, $start);
        $this->assertSame('booked', $appt2->status);
    }

    public function test_late_cancellation_forfeits_credit(): void
    {
        $trainer = User::factory()->trainer()->create();
        $start = now()->addHours(5); // within 24h
        TrainerAvailability::create([
            'trainer_id' => $trainer->id,
            'starts_at' => $start->copy()->subHour(),
            'ends_at' => $start->copy()->addHours(2),
        ]);
        $student = User::factory()->student()->create();
        $this->service->grantCredits($student, 1);

        $appt = $this->service->book($student, $trainer, $start);
        $this->service->cancel($appt, $student);

        // spent 1, no refund → balance 0
        $this->assertSame(0, SessionCreditLedger::balanceFor($student->id));
    }

    public function test_early_cancellation_refunds_credit(): void
    {
        [$trainer, $start] = $this->trainerWithSlot(); // 5 days out
        $student = User::factory()->student()->create();
        $this->service->grantCredits($student, 1);

        $appt = $this->service->book($student, $trainer, $start);
        $this->service->cancel($appt, $student);

        // spent 1, refunded 1 → balance 1
        $this->assertSame(1, SessionCreditLedger::balanceFor($student->id));
    }
}
