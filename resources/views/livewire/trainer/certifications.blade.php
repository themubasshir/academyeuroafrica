<div class="mx-auto max-w-6xl space-y-6">
    <a href="{{ route('trainer.dashboard') }}" class="link text-sm">&larr; Dashboard</a>
    <x-ui.page-head :register="$course->programme->name" :title="'Certifications — '.$course->title" folio="{{ $rows->count() }} students" />
    <x-trainer.course-nav :course="$course" active="certifications" />

    @if (session('saved'))<div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">{{ session('saved') }}</div>@endif

    <div class="ledger">
        @forelse ($rows as $row)
            @php($e = $row['enrolment'])(@php($r = $row['result']))
            <div class="px-5 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink-900">{{ $e->user->name }}</p>
                        <p class="mt-0.5 text-xs text-ink-500">
                            @if ($r['status'] === 'eligible') Eligible
                            @elseif ($r['status'] === 'pending_approval')
                                @if ($e->certificate && $e->certificate->status === 'issued') Issued — {{ $e->certificate->number }}
                                @else Awaiting final approval @endif
                            @elseif ($r['status'] === 'disabled') Certifications off
                            @else {{ $r['percent'] }}% — missing: {{ collect($r['checks'])->where('required')->where('done', false)->map->detail->join('; ') }}
                            @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($e->certificate)<span class="font-mono text-xs text-ink-500">{{ $e->certificate->number }}</span>@endif
                        @if ($r['status'] === 'pending_approval' && ! $e->certificate)
                            <button wire:click="approve({{ $e->id }})" class="btn-primary btn-sm">Approve completion</button>
                        @endif
                        <button wire:click="requestReevaluation({{ $e->id }})" class="btn-secondary btn-sm">Re-evaluate</button>
                    </div>
                </div>
            </div>
        @empty
            <x-ui.empty title="No students enrolled" />
        @endforelse
    </div>
</div>
