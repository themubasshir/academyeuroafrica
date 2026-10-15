<div class="mx-auto max-w-6xl space-y-6">
    <x-ui.page-head register="Administration" title="Certificates" folio="{{ $certs->total() }} records" />

    @if (session('saved'))<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">{{ session('saved') }}</div>@endif

    <div class="flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" placeholder="Search number, recipient, course…" class="input max-w-sm">
        <select wire:model.live="status" class="input w-40">
            <option value="all">All statuses</option>
            <option value="issued">Issued</option>
            <option value="revoked">Revoked</option>
            <option value="superseded">Superseded</option>
        </select>
    </div>

    <div class="ledger">
        @forelse ($certs as $cert)
            <div class="px-5 py-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    <p class="font-mono text-sm font-semibold text-ink-900">{{ $cert->number }}</p>
                    <p class="mt-0.5 text-xs text-ink-600">{{ $cert->recipient_name }} — {{ $cert->course_title }}</p>
                    <p class="mt-0.5 text-xs text-ink-500">issued {{ $cert->issued_at->format('j M Y') }} · ref <code class="font-mono">{{ $cert->public_ref }}</code></p>
                </div>
                <div class="mt-3 flex shrink-0 flex-wrap items-center gap-2 sm:mt-0">
                    @if ($cert->status === 'issued')<span class="badge-green">Issued</span>
                    @elseif ($cert->status === 'revoked')<span class="badge-red">Revoked</span>
                    @else<span class="badge-gray">Superseded</span>@endif
                    <a href="{{ $cert->verificationUrl() }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">Verify</a>
                    @if ($cert->pdf_path)
                        <a href="{{ route('student.certificates.download', $cert) }}" class="btn-secondary btn-sm">PDF</a>
                    @endif
                    @if ($cert->status === 'issued')
                        <button wire:click="startRevoke({{ $cert->id }})" class="btn-ghost btn-sm text-red-700">Revoke</button>
                    @endif
                </div>
            </div>
            @if ($revokingId === $cert->id)
                <div class="border-t border-red-200 bg-red-50/60 px-5 py-4">
                    <p class="text-sm font-semibold text-red-900">Revoke {{ $cert->number }}?</p>
                    <p class="mt-1 text-xs text-red-800">This is permanent. The verification page will show the credential as revoked.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <input wire:model="revokeReason" placeholder="Reason (required, internal log)" class="input input-sm flex-1" required>
                        <button wire:click="revoke" class="btn-primary btn-sm bg-red-700 hover:bg-red-800">Confirm revoke</button>
                        <button wire:click="$set('revokingId', null)" class="btn-ghost btn-sm">Cancel</button>
                    </div>
                    @error('revokeReason')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
            @endif
        @empty
            <x-ui.empty title="No certificates" />
        @endforelse
    </div>
    {{ $certs->links() }}

    {{-- Reconciliation: enrolments on certifiable courses without an active cert --}}
    @if ($missing->isNotEmpty())
        <section>
            <div class="register-rule"><h2 class="register shrink-0">Pending issuance</h2></div>
            <div class="ledger mt-4">
                @foreach ($missing as $e)
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                        <div>
                            <p class="text-sm font-semibold text-ink-900">{{ $e->user->name }}</p>
                            <p class="text-xs text-ink-500">{{ $e->variant->course->title }} · enrolled {{ $e->enrolled_at->format('j M Y') }}</p>
                        </div>
                        <button wire:click="manualIssue({{ $e->id }})" class="btn-secondary btn-sm">Evaluate &amp; issue</button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
