@props(['value' => 0, 'max' => 100, 'tone' => 'eamn'])
@php $pct = $max > 0 ? min(100, round($value / $max * 100)) : 0; @endphp
<div class="w-full">
    <div class="flex items-center justify-between text-xs text-gray-500">
        <span>{{ $value }}/{{ $max }}</span><span>{{ $pct }}%</span>
    </div>
    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
        <div @class(['h-full rounded-full transition-all', 'bg-eamn-600' => $tone === 'eamn', 'bg-green-500' => $tone === 'green'])
             style="width: {{ $pct }}%"></div>
    </div>
</div>
