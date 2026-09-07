@props([
    'value' => 0,
    'label' => null,
    'trail' => null,
])

{{-- Tahap 2 · Bar progres. Width is the only animated value in the app. --}}
@php
    $percent = max(0, min(100, (int) $value));
    $complete = $percent >= 100;
@endphp

<div {{ $attributes }}>
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-muted"
         role="progressbar"
         aria-valuenow="{{ $percent }}"
         aria-valuemin="0"
         aria-valuemax="100"
         @if ($label) aria-label="{{ $label }}" @endif>
        <span class="block h-full rounded-full transition-[width] duration-300 ease-out {{ $complete ? 'bg-success' : 'bg-accent' }}"
              style="width: {{ $percent }}%"></span>
    </div>

    @if ($label || $trail)
        <div class="mt-2 flex justify-between text-xs text-muted">
            <span>{{ $label }}</span>
            <span>{{ $trail }}</span>
        </div>
    @endif
</div>
