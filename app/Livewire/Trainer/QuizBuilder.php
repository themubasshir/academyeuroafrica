<?php

namespace App\Livewire\Trainer;

use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Livewire\Component;

class QuizBuilder extends Component
{
    public Quiz $quiz;

    // settings form
    public ?int $timeLimit = null;

    public int $maxAttempts = 1;

    public ?int $passingScore = null;

    public bool $isRequired = true;

    public string $releaseResults = 'after_close';

    public bool $randomiseQuestions = false;

    public bool $randomiseOptions = false;

    // question form
    public string $qType = 'mcq';

    public string $qPrompt = '';

    public int $qPoints = 1;

    public array $qOptions = []; // [['label'=>'','correct'=>bool], ...]

    public function mount(Quiz $quiz): void
    {
        $this->authorize('manageContent', $quiz->course);
        $this->syncSettings();
        $this->addOption();
        $this->addOption();
    }

    private function syncSettings(): void
    {
        $this->timeLimit = $this->quiz->time_limit_minutes;
        $this->maxAttempts = $this->quiz->max_attempts ?? 1;
        $this->passingScore = $this->quiz->passing_score;
        $this->isRequired = $this->quiz->is_required ?? true;
        $this->releaseResults = $this->quiz->release_results ?? 'after_close';
        $this->randomiseQuestions = (bool) $this->quiz->randomise_questions;
        $this->randomiseOptions = (bool) $this->quiz->randomise_options;
    }

    public function addOption(): void
    {
        $this->qOptions[] = ['label' => '', 'correct' => false];
    }

    public function saveSettings(): void
    {
        $this->validate([
            'timeLimit' => ['nullable', 'integer', 'min:1'],
            'maxAttempts' => ['required', 'integer', 'min:1', 'max:100'],
            'passingScore' => ['nullable', 'integer', 'min:0'],
            'releaseResults' => ['required', 'in:immediate,after_close,manual'],
            'isRequired' => ['boolean'],
        ]);
        $this->quiz->update([
            'time_limit_minutes' => $this->timeLimit,
            'max_attempts' => max(1, $this->maxAttempts),
            'passing_score' => $this->passingScore,
            'is_required' => $this->isRequired,
            'release_results' => $this->releaseResults,
            'randomise_questions' => $this->randomiseQuestions,
            'randomise_options' => $this->randomiseOptions,
        ]);
        session()->flash('saved', 'Settings saved.');
    }

    public function addQuestion(): void
    {
        $this->validate([
            'qPrompt' => 'required|string',
            'qPoints' => 'required|integer|min:1',
            'qType' => 'required|in:mcq,multi,tf,short,essay',
        ]);

        $question = $this->quiz->questions()->create([
            'type' => $this->qType,
            'prompt' => $this->qPrompt,
            'points' => $this->qPoints,
            'sort_order' => $this->quiz->questions()->max('sort_order') + 1,
        ]);

        $choiceTypes = ['mcq', 'multi', 'tf'];
        if (in_array($this->qType, $choiceTypes, true)) {
            $hasCorrect = collect($this->qOptions)->contains('correct', true);
            abort_unless($hasCorrect, 422, 'Mark at least one option correct.');
            foreach ($this->qOptions as $i => $opt) {
                if (trim($opt['label']) === '') {
                    continue;
                }
                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => $opt['label'],
                    'is_correct' => (bool) $opt['correct'],
                    'sort_order' => $i,
                ]);
            }
        } elseif ($this->qType === 'short') {
            // acceptable answers stored as correct options
            foreach ($this->qOptions as $i => $opt) {
                if (trim($opt['label']) === '') {
                    continue;
                }
                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => $opt['label'],
                    'is_correct' => true,
                    'sort_order' => $i,
                ]);
            }
        }

        $this->reset(['qPrompt', 'qOptions']);
        $this->addOption();
        $this->addOption();
    }

    public function deleteQuestion(int $id): void
    {
        $this->quiz->questions()->where('id', $id)->delete();
    }

    public function moveQuestion(int $id, string $dir): void
    {
        $ids = $this->quiz->questions()->orderBy('sort_order')->pluck('id')->values();
        $i = $ids->search($id);
        $swap = $dir === 'up' ? $ids->get($i - 1) : $ids->get($i + 1);
        if ($i === false || $swap === null) {
            return;
        }
        $a = QuizQuestion::find($id);
        $b = QuizQuestion::find($swap);
        [$a->sort_order, $b->sort_order] = [$b->sort_order, $a->sort_order];
        $a->save();
        $b->save();
    }

    public function publish(): void
    {
        abort_unless($this->quiz->questions()->exists(), 422, 'Add at least one question.');
        $this->quiz->update(['status' => 'published']);
    }

    public function unpublish(): void
    {
        $this->quiz->update(['status' => 'draft']);
    }

    public function render()
    {
        return view('livewire.trainer.quiz-builder', [
            'questions' => $this->quiz->questions()->with('options')->orderBy('sort_order')->get(),
        ])->layout('layouts::app', ['title' => 'Build: '.$this->quiz->title]);
    }
}
