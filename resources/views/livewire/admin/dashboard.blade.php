<div class="space-y-8">
    <x-ui.page-head register="Administration" title="Academy administration" />

    <section aria-label="Registry totals" class="grid gap-x-8 gap-y-6 sm:grid-cols-3">
        <x-ui.stat label="Programmes" :value="\App\Models\Programme::count()" />
        <x-ui.stat label="Courses" :value="\App\Models\Course::count()" />
        <x-ui.stat label="Users" :value="\App\Models\User::count()" />
        <x-ui.stat label="Enrolments" :value="\App\Models\Enrolment::count()" />
        <x-ui.stat label="Orders" :value="\App\Models\Order::count()" />
        <x-ui.stat label="Revenue" :value="'€'.number_format(\App\Models\Order::where('status','paid')->sum('total_cents')/100, 2)" tone="seal" />
    </section>

    <section aria-labelledby="ops-heading">
        <div class="register-rule"><h2 id="ops-heading" class="register shrink-0">Operations</h2></div>
        <div class="ledger mt-4">
            @foreach ([
                ['Programmes', 'admin.programmes', 'Manage programmes and publishing'],
                ['Courses', 'admin.courses', 'Manage course records'],
                ['Users', 'admin.users', 'Accounts and roles'],
                ['Enrolments', 'admin.enrolments', 'Student enrolment register'],
                ['Orders', 'admin.orders', 'Payments and fulfilment'],
                ['Integrations', 'admin.integrations', 'PayPal and Joomla status'],
                ['Audit logs', 'admin.audit-logs', 'Change history'],
            ] as [$label, $route, $hint])
                <a href="{{ route($route) }}" class="ledger-row">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-ink-900">{{ $label }}</span>
                        <span class="block text-xs text-ink-500">{{ $hint }}</span>
                    </span>
                    <span class="shrink-0 text-sm font-semibold text-reg-700">Open →</span>
                </a>
            @endforeach
        </div>
    </section>
</div>
