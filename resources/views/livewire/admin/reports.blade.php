<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Operational reports" />

    <div class="card-pad">
        <div class="grid gap-4 sm:grid-cols-3">
            <label class="text-sm font-medium">From
                <input wire:model.live="from" type="date" class="input mt-1">
            </label>
            <label class="text-sm font-medium">To
                <input wire:model.live="to" type="date" class="input mt-1">
            </label>
        </div>
    </div>

    <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat label="Students" :value="$students" />
        <x-ui.stat label="Enrolments" :value="$enrolments" />
        <x-ui.stat label="Active access" :value="$activeEnrolments" />
        <x-ui.stat label="Paid orders" :value="$paidOrders" />
        <x-ui.stat label="Revenue" :value="'€'.number_format($revenueCents / 100, 2)" tone="seal" />
        <x-ui.stat label="Completed refunds" :value="'€'.number_format($refundsCents / 100, 2)" />
        <x-ui.stat label="Certificates issued" :value="$certificates" />
    </section>

    <section class="card overflow-x-auto">
        <div class="card-pad"><h2 class="h3">Participation and completion</h2></div>
        <table class="table-base">
            <thead class="bg-paper-50"><tr><th>Course</th><th class="text-right">Enrolments</th><th class="text-right">Completed</th><th class="text-right">Completion</th></tr></thead>
            <tbody>
                @forelse ($completionByCourse as $course)
                    <tr>
                        <td class="font-medium">{{ $course->title }}</td>
                        <td class="text-right">{{ $course->enrolments }}</td>
                        <td class="text-right">{{ $course->completed }}</td>
                        <td class="text-right">{{ $course->enrolments ? number_format(($course->completed / $course->enrolments) * 100, 1) : '0.0' }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-ink-500">No enrolment records in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
