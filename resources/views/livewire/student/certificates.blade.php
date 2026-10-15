<div class="mx-auto max-w-5xl space-y-8">
    <x-ui.page-head register="My records" title="My Certificates" folio="{{ $certs->count() }} issued" />

    {{-- Earned --}}
    @if ($certs->isNotEmpty())
        <section>
            <div class="register-rule"><h2 class="register shrink-0">Issued certificates</h2></div>
            <ul class="mt-4 space-y-4">
                @foreach ($certs as $cert)
                    <li class="card-pad flex flex-wrap items-center justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-lg font-semibold text-ink-950">{{ $cert->course_title }}</p>
                            <p class="mt-1 font-mono text-xs text-ink-500">{{ $cert->number }} · issued {{ $cert->issued_at->format('j M Y') }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if ($cert->isRevoked())
                                <span class="badge-red">Revoked</span>
                            @else
                                <span class="badge-green">Valid</span>
                                <a href="{{ route('student.certificates.download', $cert) }}" class="btn-primary btn-sm">Download PDF</a>
                            @endif
                            <a href="{{ $cert->verificationUrl() }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">Verify</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- In-progress --}}
    @if ($progress->isNotEmpty())
        <section>
            <div class="register-rule"><h2 class="register shrink-0">Working toward</h2></div>
            <ul class="mt-4 space-y-4">
                @foreach ($progress as $row)
                    @php($r = $row['result'])
                    <li class="card-pad">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-display font-semibold text-ink-950">{{ $row['enrolment']->variant->course->title }}</p>
                                <p class="mt-0.5 text-sm text-ink-600">
                                    @if ($r['status'] === 'eligible' && $row['issued'])
                                        Certificate issued
                                    @elseif ($r['status'] === 'eligible')
                                        Eligible — certificate being issued
                                    @elseif ($r['status'] === 'pending_approval')
                                        Complete — awaiting approval
                                    @else
                                        In progress
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-display text-2xl font-semibold text-ink-950">{{ $r['percent'] }}<span class="text-base text-ink-500">%</span></p>
                            </div>
                        </div>
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-paper-200" role="progressbar" aria-valuenow="{{ $r['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full bg-reg-600" style="width: {{ $r['percent'] }}%"></div>
                        </div>
                        <dl class="mt-4 space-y-1.5 border-t border-paper-200 pt-4">
                            @foreach ($r['checks'] as $key => $check)
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <dt class="text-ink-700">{{ str_replace('_', ' ', ucfirst($key)) }}</dt>
                                    <dd @class(['flex items-center gap-1.5 text-xs font-medium', 'text-green-700' => $check['done'], 'text-ink-500' => ! $check['done']])>
                                        {{ $check['detail'] }}
                                        @if ($check['done'])<span class="font-bold">✓</span>@endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($certs->isEmpty() && $progress->isEmpty())
        <x-ui.empty title="No certificates" message="Certificates for courses with certification enabled appear here when you complete all requirements." />
    @endif
</div>
