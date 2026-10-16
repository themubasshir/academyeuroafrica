<?php

namespace App\Livewire\Trainer;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\LiveSession;
use App\Models\User;
use App\Notifications\StudentAccountNotification;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Sessions extends Component
{
    public Course $course;

    public string $title = '';

    public string $startsAt = '';

    public int $duration = 60;

    public string $provider = 'zoom';

    public string $meetingUrl = '';

    public string $audience = 'course'; // course|group|student

    public ?int $groupId = null;

    public ?int $studentId = null;

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'startsAt' => 'required|date|after:now',
            'duration' => 'required|integer|min:15|max:480',
            'provider' => 'required|in:zoom,teams',
            'meetingUrl' => 'required|url',
            'audience' => 'required|in:course,group,student',
        ]);

        if ($this->audience === 'group') {
            abort_unless($this->course->groups()->whereKey($this->groupId)->exists(), 422, 'Group is not part of this course.');
        }
        if ($this->audience === 'student') {
            abort_unless(Enrolment::where('user_id', $this->studentId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->whereHas('variant', fn ($query) => $query->where('course_id', $this->course->id))
                ->exists(), 422, 'Student is not actively enrolled in this course.');
        }

        $session = LiveSession::create([
            'course_id' => $this->course->id,
            'trainer_id' => auth()->id(),
            'group_id' => $this->audience === 'group' ? $this->groupId : null,
            'student_id' => $this->audience === 'student' ? $this->studentId : null,
            'title' => $this->title,
            'starts_at' => Carbon::parse($this->startsAt, auth()->user()->timezone ?? 'UTC')->utc(),
            'duration_minutes' => $this->duration,
            'provider' => $this->provider,
            'meeting_url' => $this->meetingUrl,
            'status' => 'scheduled',
        ]);

        $this->notifySessionAudience($session, 'Live training scheduled', 'A live training session has been scheduled: '.$session->title.'.');

        $this->reset(['title', 'startsAt', 'meetingUrl', 'groupId', 'studentId']);
        session()->flash('saved', 'Session scheduled.');
    }

    public function cancel(int $id): void
    {
        $session = LiveSession::where('id', $id)->where('course_id', $this->course->id)->firstOrFail();
        if ($session->status === 'cancelled') {
            return;
        }

        $session->update(['status' => 'cancelled']);
        $this->notifySessionAudience($session, 'Live training cancelled', 'The live training session '.$session->title.' has been cancelled.');
    }

    private function notifySessionAudience(LiveSession $session, string $title, string $message): void
    {
        $recipients = match (true) {
            $session->student_id !== null => User::whereKey($session->student_id),
            $session->group_id !== null => $session->group()->firstOrFail()->members(),
            default => User::whereHas('enrolments', fn ($query) => $query
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->whereHas('variant', fn ($query) => $query->where('course_id', $session->course_id))),
        };

        $recipients->get()->each(fn (User $student) => $student->notify(new StudentAccountNotification(
            $title,
            $message,
            route('student.dashboard'),
            'classes',
        )));
    }

    public function render()
    {
        return view('livewire.trainer.sessions', [
            'sessions' => LiveSession::where('course_id', $this->course->id)
                ->orderBy('starts_at')->with(['group', 'student'])->get(),
            'groups' => $this->course->groups()->with('members')->get(),
            'students' => Enrolment::whereHas('variant', fn ($q) => $q->where('course_id', $this->course->id))
                ->where('status', 'active')->with('user')->get()->pluck('user'),
        ])->layout('layouts::app', ['title' => 'Live sessions']);
    }
}
