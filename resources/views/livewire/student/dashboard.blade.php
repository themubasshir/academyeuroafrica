<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Pick up where you left off.</p>
    </div>

    <section aria-labelledby="continue-heading">
        <h2 id="continue-heading" class="text-lg font-medium">Continue learning</h2>
        <div class="mt-3 rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
            No courses yet. Purchased courses appear here.
        </div>
    </section>

    <div class="grid gap-6 sm:grid-cols-2">
        <section aria-labelledby="live-heading">
            <h2 id="live-heading" class="text-lg font-medium">Upcoming live classes</h2>
            <div class="mt-3 rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                None scheduled.
            </div>
        </section>
        <section aria-labelledby="due-heading">
            <h2 id="due-heading" class="text-lg font-medium">Assignments due</h2>
            <div class="mt-3 rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
                Nothing due.
            </div>
        </section>
    </div>
</div>
