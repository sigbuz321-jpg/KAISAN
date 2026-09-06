@props([
    'tone' => 'info',
    'title' => null,
])

{{--
    Tahap 2 · Banner pesan.

    Meaning is carried by an icon and words, never by colour alone, so the
    message still reads for a colour-blind student.
--}}
@php
    $tones = [
        'info' => ['class' => 'bg-info-soft text-info-text', 'icon' => 'icon.info'],
        'success' => ['class' => 'bg-success-soft text-success-text', 'icon' => 'icon.check-circle'],
        'warning' => ['class' => 'bg-warning-soft text-warning-text', 'icon' => 'icon.warning'],
        'danger' => ['class' => 'bg-danger-soft text-danger-text', 'icon' => 'icon.x-circle'],
    ];

    $current = $tones[$tone] ?? $tones['info'];
@endphp

<div {{ $attributes->merge([
    'class' => 'flex items-start gap-3 rounded-md px-4 py-3 text-sm leading-relaxed ' . $current['class'],
]) }} role="{{ in_array($tone, ['danger', 'warning'], true) ? 'alert' : 'status' }}">
    <x-dynamic-component :component="$current['icon']" class="mt-px h-5 w-5 shrink-0" />

    <div class="min-w-0">
        @if ($title)
            <strong class="mb-0.5 block font-semibold">{{ $title }}</strong>
        @endif

        {{ $slot }}
    </div>
</div>
