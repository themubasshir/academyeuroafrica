<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('trainer.dashboard') }}" class="link text-sm">&larr; Dashboard</a>
        <div class="mt-3"><p class="register">{{ $course->programme->name }}</p><h1 class="h1 mt-1.5">{{ $course->title }}</h1></div>
    </div>
    <x-trainer.course-nav :course="$course" active="quizzes" />

    <form wire:submit="create" class="flex gap-2 card p-4">
        <input wire:model="title" placeholder="New quiz title" class="flex-1 input">
        <button class="btn-primary">Create & build</button>
    </form>

    <div class="space-y-2">
        @forelse ($quizzes as $quiz)
            <div class="flex items-center justify-between card p-4">
                <div>
                    <p class="font-medium text-sm">{{ $quiz->title }}</p>
                    <p class="text-xs text-ink-500">{{ $quiz->questions_count }} questions · {{ $quiz->attempts_count }} attempts · {{ ucfirst($quiz->status) }} · {{ $quiz->is_required ? 'Required' : 'Optional' }}</p>
                </div>
                <div class="flex gap-3 text-sm">
                    <a href="{{ route('trainer.quiz.builder', $quiz) }}" class="link">Edit</a>
                    <a href="{{ route('trainer.quiz.attempts', $quiz) }}" class="link">Attempts</a>
                </div>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-paper-300 bg-white p-6 text-center text-sm text-ink-500">No quizzes.</p>
        @endforelse
    </div>
</div>
