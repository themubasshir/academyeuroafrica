<?php

namespace App\Livewire\Trainer;

use App\Domain\Certification\CertificateTrigger;
use App\Models\Enrolment;
use App\Models\LiveSession;
use App\Models\SessionAttendance as Attendance;
use App\Models\TrainerAssignment;
use Livewire\Component;

class SessionAttendance extends Component
{
    public LiveSession $session;

    public array $attendance = []; // user_id => bool

    public function mount(LiveSession $session): void
    {
        // Trainer owns the session OR is assigned to its course; admins via Gate::before.
        $ok = $session->trainer_id === auth()->id()
            || TrainerAssignment::where('trainer_id', auth()->id())
                ->where('course_id', $session->course_id)->exists();
        abort_unless(auth()->user()->isAdmin() || $ok, 403);
        $this->session = $session;

        $this->attendance = Attendance::where('live_session_id', $session->id)
            ->pluck('attended', 'user_id')->toArray();
    }

    private function roster()
    {
        // Who could attend: whole-course → all active enrolments; group → members; student → them.
        if ($this->session->student_id) {
            return collect([$this->session->student]);
        }
        if ($this->session->group_id) {
            return $this->session->group->members;
        }

        return Enrolment::where('status', 'active')
            ->whereHas('variant', fn ($q) => $q->where('course_id', $this->session->course_id))
            ->with('user')->get()->pluck('user');
    }

    public function save(): void
    {
        foreach ($this->roster() as $user) {
            Attendance::updateOrCreate(
                ['live_session_id' => $this->session->id, 'user_id' => $user->id],
                [
                    'attended' => (bool) ($this->attendance[$user->id] ?? false),
                    'marked_by' => auth()->id(),
                    'marked_at' => now(),
                ]
            );

            // Attendance change may unlock certification.
            $enrolment = Enrolment::where('user_id', $user->id)->where('status', 'active')
                ->whereHas('variant', fn ($q) => $q->where('course_id', $this->session->course_id))
                ->first();
            if ($enrolment) {
                CertificateTrigger::for($enrolment);
            }
        }
        session()->flash('saved', 'Attendance saved.');
    }

    public function render()
    {
        return view('livewire.trainer.session-attendance', [
            'roster' => $this->roster(),
        ])->layout('layouts::app', ['title' => 'Attendance: '.$this->session->title]);
    }
}
