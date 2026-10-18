<?php

namespace App\Livewire\Student;

use App\Domain\Assessment\QuizService;
use App\Models\Enrolment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Livewire\Component;

class QuizTake extends Component
{
    public Enrolment $enrolment;

    public Quiz $quiz;

    public ?QuizAttempt $attempt = null;

    /** question_id => chosen option ids (array for multi) or text */
    public array $responses = [];

    public function mount(Enrolment $enrolment, Quiz $quiz): void
    {
        $this->authorize('view', $enrolment);
        abort_unless($enrolment->isActive(), 403, 'This enrolment has expired.');
        abort_unless($quiz->course_id === $enrolment->variant->course_id, 404);
        abort_unless($quiz->status === 'published', 404);

        $this->attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->latest()->first();
    }

    public function start(QuizService $service): void
    {
        abort_unless($this->enrolment->isActive(), 403);
        $this->attempt = $service->startAttempt($this->quiz, auth()->user());
    }

    public function submit(QuizService $service): void
    {
        abort_unless($this->enrolment->isActive(), 403);
        abort_if($this->attempt === null, 422);

        foreach ($this->responses as $questionId => $response) {
            $question = $this->quiz->questions->firstWhere('id', $questionId);
            $isText = in_array($question?->type, ['short', 'essay'], true);

            if ($isText) {
                $service->answer($this->attempt, (int) $questionId, [], is_string($response) ? $response : null);
            } else {
                $ids = is_array($response)
                    ? array_map('intval', array_filter($response))
                    : (is_numeric($response) ? [(int) $response] : []);
                $service->answer($this->attempt, (int) $questionId, $ids);
            }
        }

        $this->attempt = $service->submit($this->attempt);
    }

    public function render()
    {
        $questions = collect();
        $result = null;

        if ($this->attempt !== null) {
            $ordered = $this->quiz->questions()->with('options')
                ->get()->sortBy(fn ($q) => array_search($q->id, $this->attempt->question_order ?? []));
            $questions = $ordered;

            if (app(QuizService::class)->resultsVisible($this->attempt)) {
                $result = $this->attempt;
            }
        }

        return view('livewire.student.quiz-take', [
            'questions' => $questions,
            'result' => $result,
            'finished' => $this->attempt !== null && $this->attempt->status !== 'in_progress',
        ])->layout('layouts::app', ['title' => $this->quiz->title]);
    }
}
