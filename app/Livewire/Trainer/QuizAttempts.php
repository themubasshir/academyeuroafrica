<?php

namespace App\Livewire\Trainer;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use Livewire\Component;

class QuizAttempts extends Component
{
    public Quiz $quiz;

    public function mount(Quiz $quiz): void
    {
        $this->authorize('manageContent', $quiz->course);
    }

    /** Release/award points on a manual-graded answer (scoped to this quiz). */
    public function award(int $answerId, int $points): void
    {
        $a = QuizAnswer::where('id', $answerId)
            ->whereHas('attempt', fn ($q) => $q->where('quiz_id', $this->quiz->id))->firstOrFail();
        $a->update(['awarded_points' => $points]);
    }

    public function finalise(int $attemptId): void
    {
        $attempt = $this->quiz->attempts()->findOrFail($attemptId);
        $attempt->update([
            'score' => (int) $attempt->answers()->sum('awarded_points'),
            'status' => 'graded',
        ]);
    }

    public function render()
    {
        return view('livewire.trainer.quiz-attempts', [
            'attempts' => $this->quiz->attempts()->with(['user', 'answers.question'])->latest()->get(),
        ])->layout('layouts::app', ['title' => 'Attempts: '.$this->quiz->title]);
    }
}
