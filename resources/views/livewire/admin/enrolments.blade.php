<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight">Enrolments</h1>
    <div class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
        @forelse ($enrolments as $e)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium text-sm">{{ $e->user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $e->variant->course->title }} · {{ $e->variant->name }}</p>
                </div>
                <p class="text-xs text-gray-500">{{ $e->enrolled_at->format('M j, Y') }} · {{ ucfirst($e->status) }}</p>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-gray-500">No enrolments.</p>
        @endforelse
    </div>
</div>
