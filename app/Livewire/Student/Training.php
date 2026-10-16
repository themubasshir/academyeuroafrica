<?php

namespace App\Livewire\Student;

use App\Domain\Scheduling\BookingService;
use App\Models\Appointment;
use App\Models\SessionCreditLedger;
use App\Models\TrainerAvailability;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Training extends Component
{
    public ?int $trainerId = null;

    public ?string $slot = null;

    public function mount(): void
    {
        //
    }

    /** Available trainers = users holding trainer role with published availability. */
    public function getTrainersProperty()
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'trainer'))
            ->whereHas('availabilities', fn ($q) => $q->where('ends_at', '>', now()))
            ->get();
    }

    public function book(BookingService $service): void
    {
        $this->validate(['trainerId' => 'required|exists:users,id', 'slot' => 'required']);
        $trainer = User::whereHas('roles', fn ($query) => $query->where('name', 'trainer'))->findOrFail($this->trainerId);
        $start = Carbon::parse($this->slot, auth()->user()->timezone ?? 'UTC')->utc();

        $service->book(auth()->user(), $trainer, $start);
        $this->reset(['trainerId', 'slot']);
        session()->flash('booked', 'Session booked.');
    }

    public function cancel(BookingService $service, int $appointmentId): void
    {
        $appt = Appointment::where('id', $appointmentId)
            ->where('student_id', auth()->id())->firstOrFail();
        $service->cancel($appt, auth()->user());
        session()->flash('booked', 'Session cancelled.');
    }

    public function render()
    {
        $user = auth()->user();

        $openSlots = collect();
        if ($this->trainerId) {
            $openSlots = TrainerAvailability::where('trainer_id', $this->trainerId)
                ->where('ends_at', '>', now())
                ->orderBy('starts_at')->get()
                ->flatMap(function ($avail) {
                    // Offer 60-min starts within each availability block,
                    // excluding already-booked times.
                    $booked = Appointment::where('trainer_id', $avail->trainer_id)
                        ->whereIn('status', ['booked', 'completed'])
                        ->where('starts_at', '>=', $avail->starts_at)
                        ->where('starts_at', '<', $avail->ends_at)
                        ->pluck('starts_at')->map(fn ($d) => $d->timestamp);

                    $slots = [];
                    $cursor = $avail->starts_at->copy();
                    while ($cursor->copy()->addHour() <= $avail->ends_at) {
                        if ($cursor->isFuture() && ! $booked->contains($cursor->timestamp)) {
                            $slots[] = $cursor->copy();
                        }
                        $cursor->addHour();
                    }

                    return $slots;
                })->take(20)->values();
        }

        return view('livewire.student.training', [
            'trainers' => $this->trainers,
            'openSlots' => $openSlots,
            'appointments' => Appointment::where('student_id', $user->id)
                ->where('starts_at', '>', now()->subDay())
                ->with('trainer')->orderBy('starts_at')->get(),
            'credits' => SessionCreditLedger::balanceFor($user->id),
        ])->layout('layouts::app', ['title' => 'Private Training']);
    }
}
