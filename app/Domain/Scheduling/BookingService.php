<?php

namespace App\Domain\Scheduling;

use App\Models\Appointment;
use App\Models\SessionCreditLedger;
use App\Models\TrainerAvailability;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Private-training bookings. Transaction-safe: availability + overlap checks
 * and credit deduction happen inside one serialisable-critical transaction.
 */
class BookingService
{
    /**
     * Book a 1-on-1 session, spending one credit. Prevents double-booking and
     * enforces trainer availability + student credit balance.
     */
    public function book(User $student, User $trainer, Carbon $startsAt, int $durationMinutes = 60): Appointment
    {
        return DB::transaction(function () use ($student, $trainer, $startsAt, $durationMinutes) {
            $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

            // 1. Trainer must have advertised availability covering the slot.
            $available = TrainerAvailability::where('trainer_id', $trainer->id)
                ->where('starts_at', '<=', $startsAt)
                ->where('ends_at', '>=', $endsAt)
                ->lockForUpdate()
                ->exists();
            abort_unless($available, 422, 'Trainer not available at that time.');

            // 2. No overlapping active booking for the same trainer. Overlap is
            // evaluated in PHP inside the lock so it is driver-agnostic
            // (SQLite datetime() vs MySQL DATE_ADD differ).
            $conflict = Appointment::where('trainer_id', $trainer->id)
                ->whereIn('status', ['booked', 'completed'])
                ->where('starts_at', '<', $endsAt)
                ->lockForUpdate()
                ->get()
                ->contains(fn ($a) => $a->endsAt()->greaterThan($startsAt));
            abort_if($conflict, 422, 'That slot is already booked.');

            // 3. Student must hold at least one session credit.
            abort_if(SessionCreditLedger::balanceFor($student->id) < 1, 422, 'No session credits.');

            $appointment = Appointment::create([
                'trainer_id' => $trainer->id,
                'student_id' => $student->id,
                'starts_at' => $startsAt,
                'duration_minutes' => $durationMinutes,
                'status' => 'booked',
            ]);

            SessionCreditLedger::create([
                'user_id' => $student->id,
                'appointment_id' => $appointment->id,
                'delta' => -1,
                'reason' => 'booking',
            ]);

            \App\Models\AuditLog::record('appointment.booked', $appointment);

            return $appointment;
        });
    }

    /** Cancel — returns the credit if within policy. */
    public function cancel(Appointment $appointment, User $by): Appointment
    {
        return DB::transaction(function () use ($appointment, $by) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'booked', 422, 'Appointment cannot be cancelled.');

            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $by->id,
            ]);

            // Refund credit only if cancelled >24h before start (policy placeholder).
            if ($appointment->starts_at->diffInHours(now(), false) < -24) {
                SessionCreditLedger::create([
                    'user_id' => $appointment->student_id,
                    'appointment_id' => $appointment->id,
                    'delta' => 1,
                    'reason' => 'cancellation-refund',
                ]);
            }

            \App\Models\AuditLog::record('appointment.cancelled', $appointment, ['by' => $by->id]);

            return $appointment;
        });
    }

    /** Grant credits (e.g. after a package purchase). */
    public function grantCredits(User $student, int $count, ?int $orderId = null, string $reason = 'purchase'): void
    {
        SessionCreditLedger::create([
            'user_id' => $student->id,
            'order_id' => $orderId,
            'delta' => $count,
            'reason' => $reason,
        ]);
    }
}
