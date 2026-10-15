@props(['name' => '', 'size' => 'h-9 w-9'])
@php
    $initials = collect(explode(' ', trim($name)))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->join('');
@endphp
<span {{ $attributes->merge(['class' => "inline-flex $size items-center justify-center rounded-full bg-eamn-100 text-xs font-semibold text-eamn-800"]) }}
      aria-hidden="true">{{ strtoupper($initials) }}</span>
