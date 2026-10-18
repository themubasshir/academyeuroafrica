@props([
    'open',
    'close',
    'titleId',
    'descriptionId' => null,
    'size' => 'md',
    'confirmClose' => false,
    'mobileFullscreen' => false,
])

@php
    $width = match ($size) {
        'lg' => 'sm:max-w-3xl',
        'xl' => 'sm:max-w-5xl',
        default => 'sm:max-w-xl',
    };
@endphp

<div
    x-data="{
        shown: @entangle($open),
        dirty: false,
        returnFocus: null,
        close() {
            if ({{ $confirmClose ? 'this.dirty' : 'false' }} && ! window.confirm('Discard your unsaved changes?')) {
                return;
            }

            this.dirty = false;
            $wire.{{ $close }}();
        },
    }"
    x-init="$watch('shown', value => {
        if (value) {
            returnFocus = document.activeElement;
            dirty = false;
            $nextTick(() => $refs.panel.querySelector('[autofocus], input, select, textarea, button')?.focus());
        } else if (returnFocus) {
            $nextTick(() => returnFocus?.focus());
        }
    })"
    x-show="shown"
    x-cloak
    x-on:keydown.escape.window="if (shown) close()"
    x-on:input.capture="if (shown) dirty = true"
    x-on:change.capture="if (shown) dirty = true"
    class="fixed inset-0 z-[70] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $titleId }}"
    @if ($descriptionId) aria-describedby="{{ $descriptionId }}" @endif
>
    <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
        <div class="fixed inset-0 bg-ink-950/65" x-on:click="close()" x-transition.opacity aria-hidden="true"></div>

        <div
            x-ref="panel"
            x-trap.inert.noscroll="shown"
            x-on:click.stop
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-[0.98]"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            class="relative flex w-full {{ $width }} flex-col overflow-hidden bg-white shadow-[0_20px_50px_rgba(4,16,32,0.28)] {{ $mobileFullscreen ? 'h-dvh sm:h-auto sm:max-h-[calc(100vh-2rem)] sm:rounded-lg' : 'max-h-[calc(100vh-1rem)] rounded-t-lg sm:rounded-lg' }}"
        >
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-paper-300 bg-paper-50 px-5 py-4 sm:px-6">
                <div class="min-w-0">{{ $header }}</div>
                <button type="button" x-on:click="close()" class="-mr-2 flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-ink-600 hover:bg-paper-100 hover:text-ink-950" aria-label="Close dialog">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
