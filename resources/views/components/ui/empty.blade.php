@props(['title' => 'Nothing here yet', 'message' => null, 'icon' => null])
<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-eamn-50 text-eamn-500">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon ?? 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-5.5a1 1 0 00-.8.4l-.9 1.2a1 1 0 01-.8.4h-3a1 1 0 01-.8-.4l-.9-1.2a1 1 0 00-.8-.4H4' }}"/>
        </svg>
    </div>
    <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ $title }}</h3>
    @if ($message)<p class="mt-1 text-sm text-gray-500">{{ $message }}</p>@endif
    {{ $slot }}
</div>
