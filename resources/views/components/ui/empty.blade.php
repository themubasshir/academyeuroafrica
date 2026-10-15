@props(['title' => 'Nothing here yet', 'message' => null])
<div {{ $attributes->merge(['class' => 'rounded-md border border-dashed border-paper-300 bg-white px-5 py-6']) }}>
    <p class="text-sm font-semibold text-ink-800">{{ $title }}</p>
    @if ($message)<p class="mt-1 text-sm text-ink-500">{{ $message }}</p>@endif
    @if (trim($slot ?? '') !== '')
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
