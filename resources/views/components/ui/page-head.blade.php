@props(['register', 'title', 'folio' => null])
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        <p class="register">{{ $register }}</p>
        <h1 class="h1 mt-1.5">{{ $title }}</h1>
    </div>
    @if ($folio)<p class="folio pb-1">{{ $folio }}</p>@endif
    {{ $slot }}
</div>
<div class="mt-4 h-px w-full bg-paper-300"></div>
