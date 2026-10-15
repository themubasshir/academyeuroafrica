<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Certificate Verification — EuroAfrica Media Academy</title>
@vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-paper-50 font-sans text-ink-950 antialiased">
<div class="mx-auto max-w-xl px-4 py-16 sm:py-24">
    <div class="text-center">
        <img src="{{ asset('brand/eman-logo.png') }}" alt="EuroAfrica Media Academy" class="mx-auto h-14 w-auto">
        <p class="register mt-4">Certificate Verification</p>
    </div>

    <div class="card-pad mt-8 text-center">
        @if ($status === 'valid')
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border-2 border-green-300 bg-green-50">
                <svg class="h-7 w-7 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="mt-4 text-sm font-bold uppercase tracking-[0.18em] text-green-800">Valid credential</p>
        @else
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border-2 border-red-300 bg-red-50">
                <svg class="h-7 w-7 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <p class="mt-4 text-sm font-bold uppercase tracking-[0.18em] text-red-800">Credential revoked</p>
        @endif

        <dl class="mt-6 space-y-3 border-t border-paper-200 pt-6 text-left">
            <div class="flex justify-between gap-4">
                <dt class="folio">Recipient</dt>
                <dd class="text-sm font-semibold text-ink-900">{{ $cert->recipient_name }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="folio">Course</dt>
                <dd class="text-sm font-semibold text-ink-900">{{ $cert->course_title }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="folio">Certificate No.</dt>
                <dd class="font-mono text-sm text-ink-900">{{ $cert->number }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="folio">Issued</dt>
                <dd class="text-sm text-ink-900">{{ $cert->issued_at->format('j F Y') }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="folio">Issued by</dt>
                <dd class="text-sm text-ink-900">{{ $cert->org_name }}</dd>
            </div>
        </dl>
    </div>

    <p class="mt-6 text-center text-xs text-ink-500">
        Verify certificates at
        <span class="font-mono">{{ str_replace('https://', '', config('app.academy_url')) }}/verify/{ref}</span>
    </p>
</div>
</body>
</html>
