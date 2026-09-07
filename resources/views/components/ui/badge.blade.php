@props(['tone' => 'neutral'])

{{-- Tahap 2 · Badge status. A -soft background is always paired with its
     matching -text colour, never with --muted. --}}
@php
    $tones = [
        'accent' => 'bg-accent-soft text-accent-text',
        'neutral' => 'bg-neutral-soft text-neutral-text',
        'success' => 'bg-success-soft text-success-text',
        'warning' => 'bg-warning-soft text-warning-text',
        'info' => 'bg-info-soft text-info-text',
        'danger' => 'bg-danger-soft text-danger-text',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex h-[22px] items-center rounded-full px-3 text-xs font-medium tracking-[0.02em] '
        . ($tones[$tone] ?? $tones['neutral']),
]) }}>
    {{ $slot }}
</span>
