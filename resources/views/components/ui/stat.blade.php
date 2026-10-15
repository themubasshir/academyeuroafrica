@props(['label', 'value', 'tone' => 'default'])
<div class="border-l-2 border-paper-300 py-1 pl-4 {{ $tone === 'seal' ? 'border-seal-400' : '' }}">
    <p class="register">{{ $label }}</p>
    <p class="mt-1.5 font-display text-3xl font-semibold leading-none tracking-normal text-ink-950">{{ $value }}</p>
</div>
