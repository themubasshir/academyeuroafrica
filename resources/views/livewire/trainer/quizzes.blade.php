<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="text-sm text-eamn-700 hover:underline">&larr; Dashboard</a>
    <h1 class="h1">Quizzes — {{ $course->title }}</h1>

    <form wire:submit="create" class="flex gap-2 card p-4">
        <input wire:model="title" placeholder="New quiz title" class="flex-1 input">
        <button class="btn-primary">Create & build</button>
    </form>

    <div class="space-y-2">
        @forelse ($quizzes as $quiz)
            <div class="flex items-center justify-between card p-4">
                <div>
                    <p class="font-medium text-sm">{{ $quiz->title }}</p>
                    <p class="text-xs text-gray-500">{{ $quiz->questions_count }} questions · {{ $quiz->attempts_count }} attempts · {{ ucfirst($quiz->status) }}</p>
                </div>
                <div class="flex gap-3 text-sm">
                    <a href="{{ route('trainer.quiz.builder', $quiz) }}" class="text-eamn-700 hover:underline">Edit</a>
                    <a href="{{ route('trainer.quiz.attempts', $quiz) }}" class="text-eamn-700 hover:underline">Attempts</a>
                </div>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">No quizzes.</p>
        @endforelse
    </div>
</div>
