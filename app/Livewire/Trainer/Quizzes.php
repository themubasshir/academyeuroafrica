<?php

namespace App\Livewire\Trainer;

use App\Models\Course;
use App\Models\Quiz;
use Livewire\Component;

class Quizzes extends Component
{
    public Course $course;

    public string $title = '';

    public function mount(Course $course): void
    {
        $this->authorize('manageContent', $course);
    }

    public function create(): void
    {
        $this->validate(['title' => 'required|string|max:255']);
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'creator_id' => auth()->id(),
            'title' => $this->title,
            'status' => 'draft',
        ]);
        $this->reset('title');

        $this->redirectRoute('trainer.quiz.builder', $quiz);
    }

    public function render()
    {
        return view('livewire.trainer.quizzes', [
            'quizzes' => Quiz::where('course_id', $this->course->id)->withCount('questions', 'attempts')->latest()->get(),
        ])->layout('layouts::app', ['title' => 'Quizzes']);
    }
}
