{{-- Flash toast region — place once in layout. Announces to screen readers. --}}
<div aria-live="polite" aria-atomic="true" class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4">
    @foreach (['saved' => 'green', 'booked' => 'green', 'error' => 'red', 'warning' => 'amber'] as $key => $tone)
        @if (session($key))
            <div @class(['pointer-events-auto flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium shadow-lg',
                'bg-green-600 text-white' => $tone === 'green',
                'bg-red-600 text-white' => $tone === 'red',
                'bg-amber-500 text-eamn-950' => $tone === 'amber'])
                 role="status">
                {{ session($key) }}
            </div>
        @endif
    @endforeach
</div>
