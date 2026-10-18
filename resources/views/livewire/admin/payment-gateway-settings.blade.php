<div class="space-y-6">
    <x-ui.page-head register="Settings" title="Payment Gateway" />

    @if ($statusMessage)
        <div class="rounded-md border border-paper-300 bg-white px-4 py-3 text-sm text-ink-700" role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <form wire:submit="saveGatewayState" class="card-pad space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="h3">PayPal checkout</h2>
                <p class="mt-1 max-w-2xl text-sm text-ink-600">Choose the environment used for new checkouts. Saving credentials or testing a connection never changes the active environment.</p>
            </div>
            <span @class([
                'badge-green' => $enabled && ($configurationStatus[$activeEnvironment]['complete'] ?? false),
                'badge-amber' => ! $enabled || ! ($configurationStatus[$activeEnvironment]['complete'] ?? false),
            ])>
                {{ $enabled ? 'Enabled' : 'Disabled' }} · {{ ucfirst($activeEnvironment) }}
            </span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <fieldset>
                <legend class="label">Active environment</legend>
                <div class="mt-2 flex flex-wrap gap-4">
                    <label class="flex min-h-11 items-center gap-2 text-sm text-ink-700">
                        <input wire:model="activeEnvironment" type="radio" value="sandbox" class="h-4 w-4 text-reg-700 focus:ring-reg-600">
                        Sandbox
                    </label>
                    <label class="flex min-h-11 items-center gap-2 text-sm text-ink-700">
                        <input wire:model="activeEnvironment" type="radio" value="live" class="h-4 w-4 text-reg-700 focus:ring-reg-600">
                        Live
                    </label>
                </div>
            </fieldset>
            <label class="flex min-h-11 items-center gap-3 text-sm font-medium text-ink-700 sm:self-end">
                <input wire:model="enabled" type="checkbox" class="h-4 w-4 rounded border-paper-300 text-reg-700 focus:ring-reg-600">
                Enable PayPal checkout
            </label>
        </div>

        @if ($activeEnvironment === 'live')
            <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Live processes real payments. This selection takes effect only when you save this gateway state.
            </div>
        @endif

        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveGatewayState">Save gateway state</button>
    </form>

    <div class="grid gap-5 xl:grid-cols-2">
        @foreach (['sandbox' => 'Sandbox', 'live' => 'Live'] as $environment => $label)
            @php $status = $configurationStatus[$environment] ?? []; @endphp
            <section class="card-pad space-y-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="h3">{{ $label }} credentials</h2>
                        <p class="mt-1 text-sm text-ink-600">Stored values remain encrypted and are never displayed again.</p>
                    </div>
                    <span class="{{ ($status['complete'] ?? false) ? 'badge-green' : 'badge-amber' }}">
                        {{ ($status['complete'] ?? false) ? 'Configured' : 'Incomplete' }}
                    </span>
                </div>

                <form wire:submit="saveCredentials('{{ $environment }}')" class="space-y-4">
                    @foreach (['client_id' => 'Client ID', 'client_secret' => 'Client Secret', 'webhook_id' => 'Webhook ID'] as $field => $fieldLabel)
                        <label class="block">
                            <span class="label">{{ $fieldLabel }}</span>
                            <input wire:model="credentials.{{ $environment }}.{{ $field }}"
                                   type="password"
                                   autocomplete="new-password"
                                   class="input mt-1"
                                   placeholder="{{ ($status[$field] ?? false) ? 'Stored securely — leave blank to keep' : 'Not configured' }}">
                        </label>
                        @error("credentials.{$environment}.{$field}")<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    @endforeach

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveCredentials('{{ $environment }}')">Save {{ $label }} credentials</button>
                        <button type="button" class="btn-secondary" wire:click="testConnection('{{ $environment }}')" wire:loading.attr="disabled" wire:target="testConnection('{{ $environment }}')" @disabled(! ($status['complete'] ?? false))>Test connection</button>
                    </div>
                </form>

                @if (($connectionStatus[$environment] ?? null) === 'success')
                    <p class="text-sm font-medium text-emerald-700" role="status">Connection verified successfully.</p>
                @elseif (($connectionStatus[$environment] ?? null) === 'failed')
                    <p class="text-sm font-medium text-red-700" role="alert">Connection failed. Credentials were not exposed.</p>
                @endif
            </section>
        @endforeach
    </div>

    <section class="card-pad">
        <h2 class="h3">Webhook endpoint</h2>
        <p class="mt-2 text-sm text-ink-600">Register this HTTPS URL separately in each PayPal app and save the matching Webhook ID above.</p>
        <code class="mt-3 block overflow-x-auto rounded-md bg-paper-100 px-3 py-2 text-sm text-ink-900">{{ route('webhooks.payment', 'paypal') }}</code>
    </section>
</div>
