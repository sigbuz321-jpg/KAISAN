@props(['level' => null])

{{--
    Tahap 2 · Badge level kemampuan.

    Takes the AbilityLevel enum, never the rating. The number behind it is
    deliberately never shown to a student.
--}}
@php
    $tones = [
        'pemula' => 'bg-info-soft text-info-text',
        'berkembang' => 'bg-warning-soft text-warning-text',
        'mahir' => 'bg-success-soft text-success-text',
        'ahli' => 'bg-master-soft text-master-text',
    ];

    $key = $level?->value;
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex h-[22px] items-center rounded-full px-3 text-xs font-medium tracking-[0.02em] '
        . ($tones[$key] ?? 'bg-neutral-soft text-neutral-text'),
]) }}>
    {{ $level?->label() ?? 'Belum dimulai' }}
</span>
