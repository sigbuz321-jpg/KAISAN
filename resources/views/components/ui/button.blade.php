@props([
    'variant' => 'primary',
    'size' => 'md',
    'block' => true,
    'processing' => false,
    'disabled' => false,
    'href' => null,
])

{{--
    Tahap 2 · Tombol.

    One primary button per visual region; anything extra drops to secondary or
    ghost. With `href` it renders as a link so navigation stays a link and
    actions stay buttons -- that distinction matters to a screen reader.

    `processing` swaps the label for a spinner and blocks the click, so a
    student on a slow connection cannot submit twice.
--}}
@php
    // A disabled button drops its variant entirely and becomes a flat neutral
    // block. Dimming the accent instead -- which is what opacity-70 did -- left
    // the primary action on the practice screen still reading as pressable, so
    // students tapped "Periksa jawaban" before choosing an option and nothing
    // happened. The hover rules are repeated under disabled: because a disabled
    // button still matches :hover.
    $base = 'inline-flex items-center justify-center gap-2 rounded-md border font-semibold '
        . 'transition-colors duration-150 focus-visible:outline focus-visible:outline-2 '
        . 'focus-visible:outline-offset-2 focus-visible:outline-accent '
        . 'disabled:cursor-not-allowed disabled:border-transparent '
        . 'disabled:bg-neutral-soft disabled:text-muted '
        . 'disabled:hover:bg-neutral-soft disabled:hover:text-muted';

    $sizes = [
        'md' => 'h-11 min-w-[88px] px-4 text-base',
        'sm' => 'h-8 min-w-[64px] px-3 text-xs',
    ];

    $variants = [
        'primary' => 'border-transparent bg-accent text-accent-fg hover:bg-accent-hover',
        'secondary' => 'border-border bg-surface text-fg hover:border-border-strong',
        'ghost' => 'border-transparent bg-transparent text-fg hover:bg-surface-muted',
        'danger' => 'border-transparent bg-danger text-danger-fg hover:opacity-90',
    ];

    $classes = implode(' ', array_filter([
        $base,
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
        $block ? 'w-full' : null,
    ]));
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }} @disabled($processing || $disabled)>
        @if ($processing)
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
            <span class="sr-only">Sedang diproses</span>
        @else
            {{ $slot }}
        @endif
    </button>
@endif
