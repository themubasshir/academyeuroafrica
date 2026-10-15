<?php

namespace App\Livewire\Catalogue;

use App\Models\Course;
use Livewire\Component;

class CourseShow extends Component
{
    public Course $course;

    public function mount(Course $course): void
    {
        $this->authorize('view', $course);
    }

    public function render()
    {
        return view('livewire.catalogue.course-show', [
            'variants' => $this->course->variants()->where('status', 'published')->get(),
            'modules' => $this->course->modules()->where('status', 'published')
                ->with(['lessons' => fn ($q) => $q->where('status', 'published')])
                ->get(),
        ])->layout('layouts::app', ['title' => $this->course->title]);
    }
}
