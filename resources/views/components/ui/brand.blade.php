@props([
    'dark' => false,   {{-- true on the navy shell / dark surfaces --}}
    'size' => 'md',    {{-- sm | md | lg --}}
])
@php
    $h = ['sm' => 'h-7', 'md' => 'h-9', 'lg' => 'h-12'][$size] ?? 'h-9';
    $word = ['sm' => 'text-sm', 'md' => 'text-lg', 'lg' => 'text-2xl'][$size] ?? 'text-lg';
    $sub  = $dark ? 'text-navy-100/85' : 'text-ink-500';
    $name = $dark ? 'text-white' : 'text-ink-950';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('brand/eman-logo.png') }}" alt="EuroAfrica Media Network"
         class="{{ $h }} w-auto select-none" width="291" height="55" loading="eager" decoding="async">
    <span class="flex flex-col leading-none">
        <span class="{{ $word }} font-display font-semibold {{ $name }}">Academy</span>
        <span class="folio mt-1 {{ $sub }}">EuroAfrica Media</span>
    </span>
</span>
