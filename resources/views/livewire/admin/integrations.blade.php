<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight">Integrations</h1>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold">Joomla bridge</h2>
            <p class="mt-1 text-sm {{ $joomlaConfigured ? 'text-green-600' : 'text-amber-600' }}">
                {{ $joomlaConfigured ? 'Configured' : 'Missing JOOMLA_BRIDGE_SECRET' }}
            </p>
            <p class="text-xs text-gray-500">{{ config('services.joomla.base_url') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold">Payments</h2>
            <p class="mt-1 text-sm {{ $stripeConfigured ? 'text-green-600' : 'text-amber-600' }}">
                {{ $stripeConfigured ? 'Stripe configured' : 'Stripe not configured' }} · default: {{ $gateway }}
            </p>
        </div>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="font-semibold">Joomla page → product map</h2>
        <table class="mt-3 w-full text-sm">
            <thead class="text-left text-xs text-gray-500"><tr><th class="pb-2">Joomla path</th><th class="pb-2">Programme</th><th class="pb-2">Variant</th></tr></thead>
            <tbody>
                @forelse ($mappings as $m)
                    <tr class="border-t border-gray-100"><td class="py-2 font-mono text-xs">{{ $m->path }}</td><td>{{ $m->programme_id ?? '—' }}</td><td>{{ $m->course_variant_id ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-500">No mappings yet — populate <code>joomla_page_mappings</code>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
