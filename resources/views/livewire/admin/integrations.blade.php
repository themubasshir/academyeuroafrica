<div class="space-y-6">
    <x-ui.page-head register="Administration" title="Integrations" />

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="card-pad">
            <h2 class="h3">Joomla bridge</h2>
            <p class="mt-1.5 text-sm font-medium {{ $joomlaConfigured ? 'text-emerald-700' : 'text-amber-700' }}">
                {{ $joomlaConfigured ? 'Configured' : 'Missing JOOMLA_BRIDGE_SECRET' }}
            </p>
            <p class="folio mt-1">{{ config('services.joomla.base_url') }}</p>
        </div>
        <div class="card-pad">
            <h2 class="h3">Payments</h2>
            <p class="mt-1.5 text-sm font-medium {{ $paypalConfigured ? 'text-emerald-700' : 'text-amber-700' }}">
                {{ $paypalConfigured ? 'PayPal configured' : 'PayPal not configured' }} · {{ $paypalMode }} · default: {{ $gateway }}
            </p>
            <a href="{{ route('admin.settings.payment-gateway') }}" class="btn-secondary btn-sm mt-4">Payment Gateway Settings</a>
        </div>
    </div>

    <section class="card-pad">
        <h2 class="h3">Joomla page → product map</h2>
        <table class="mt-3 w-full text-sm">
            <thead class="text-left text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-500"><tr><th class="pb-2">Joomla path</th><th class="pb-2">Programme</th><th class="pb-2">Variant</th></tr></thead>
            <tbody>
                @forelse ($mappings as $m)
                    <tr class="border-t border-paper-200"><td class="py-2 font-mono text-xs">{{ $m->path }}</td><td>{{ $m->programme_id ?? '—' }}</td><td>{{ $m->course_variant_id ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-ink-500">No mappings yet — populate <code>joomla_page_mappings</code>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
