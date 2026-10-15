<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight">Academy administration</h1>
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([['Programmes', 0], ['Courses', 0], ['Students', \App\Models\User::count()]] as [$label, $count])
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-3xl font-semibold">{{ $count }}</p>
            </div>
        @endforeach
    </div>
</div>
