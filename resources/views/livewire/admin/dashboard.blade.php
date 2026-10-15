<div class="space-y-6">
    <h1 class="h1">Academy administration</h1>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat label="Programmes" :value="\App\Models\Programme::count()" icon="M4 6h16M4 12h16M4 18h16" />
        <x-ui.stat label="Courses" :value="\App\Models\Course::count()" icon="M12 6v12m6-6H6" />
        <x-ui.stat label="Users" :value="\App\Models\User::count()" icon="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M12 12a4 4 0 100-8 4 4 0 000 8" />
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.stat label="Enrolments" :value="\App\Models\Enrolment::count()" tone="green" icon="M9 12l2 2 4-4" />
        <x-ui.stat label="Orders" :value="\App\Models\Order::count()" tone="green" icon="M3 3h18l-1 9H4L3 3z" />
        <x-ui.stat label="Revenue" :value="'€'.\App\Models\Order::where('status','paid')->sum('total_cents')/100" tone="amber" icon="M12 8v4l3 3" />
    </div>
</div>
