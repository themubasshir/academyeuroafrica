<?php

namespace App\Livewire\Student;

use App\Domain\Assessment\QuizService;
use App\Models\AssignmentSubmission;
use App\Models\QuizAttempt;
use Livewire\Component;

class Results extends Component
{
    public function render()
    {
        $userId = auth()->id();

        $quizAttempts = QuizAttempt::where('user_id', $userId)
            ->whereIn('status', ['submitted', 'graded'])
            ->with('quiz.course')->latest('submitted_at')->get()
            ->filter(fn ($a) => app(QuizService::class)->resultsVisible($a));

        $submissions = AssignmentSubmission::where('user_id', $userId)
            ->with(['assignment.course', 'grade'])->latest('submitted_at')->get();

        return view('livewire.student.results', compact('quizAttempts', 'submissions'))
            ->layout('layouts::app', ['title' => 'My Results']);
    }
}
