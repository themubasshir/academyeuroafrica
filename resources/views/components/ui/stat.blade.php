@props(['label', 'value', 'icon' => null, 'tone' => 'eamn'])
<div class="card-pad flex items-center gap-4">
    <div @class(['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
        'bg-eamn-50 text-eamn-600' => $tone === 'eamn',
        'bg-green-50 text-green-600' => $tone === 'green',
        'bg-amber-50 text-amber-600' => $tone === 'amber',
    ])>
        @if ($icon)<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>@endif
    </div>
    <div class="min-w-0">
        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $value }}</p>
        <p class="truncate text-sm text-gray-500">{{ $label }}</p>
    </div>
</div>
