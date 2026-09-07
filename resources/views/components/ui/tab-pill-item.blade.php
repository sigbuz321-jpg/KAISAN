@props([
    'active' => false,
    'href' => null,
])

@php
    $classes = 'min-h-11 rounded-full px-4 py-1.5 text-xs font-semibold tracking-[0.02em] '
        . 'transition-colors duration-150 inline-flex items-center '
        . ($active ? 'bg-surface text-fg shadow-[0_0_0_1px_var(--color-border)]' : 'text-muted hover:text-fg');
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate role="tab" aria-selected="{{ $active ? 'true' : 'false' }}"
       {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="button" role="tab" aria-selected="{{ $active ? 'true' : 'false' }}"
            {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
