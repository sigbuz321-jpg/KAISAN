@props([
    'href' => null,
    'title',
    'meta' => null,
    'trail' => null,
    'glyph' => null,
    'tone' => 'accent',
    'muted' => false,
])

{{-- Tahap 2 · Kartu daftar. One row of a list that leads somewhere: an exam,
     a subject, a season. --}}
@php
    $tones = [
        'accent' => 'bg-accent-soft text-accent-text',
        'info' => 'bg-info-soft text-info-text',
        'success' => 'bg-success-soft text-success-text',
        'neutral' => 'bg-surface-muted text-muted',
    ];

    $classes = 'block rounded-lg border border-border bg-surface p-4 transition-shadow duration-150'
        . ($href ? ' hover:shadow-elevated focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent' : '')
        . ($muted ? ' opacity-70' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $classes]) }}>
        @include('components.ui.partials.list-card-body')
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        @include('components.ui.partials.list-card-body')
    </div>
@endif
