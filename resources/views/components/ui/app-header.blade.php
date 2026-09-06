@props([
    'title' => null,
    'back' => null,
    'align' => 'center',
])

{{-- Tahap 2 · Header aplikasi. Sticky 56 px bar: back on the left, title in
     the middle, one optional action on the right. --}}
<header class="sticky top-0 z-10 border-b border-border bg-surface">
    <div class="mx-auto flex h-14 max-w-3xl items-center gap-3 px-4">
        @if ($back)
            <a href="{{ $back }}" wire:navigate
               class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-fg transition-colors duration-150 hover:bg-surface-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                <x-icon.arrow-left class="h-5 w-5" />
                <span class="sr-only">Kembali</span>
            </a>
        @endif

        <h1 @class([
            'min-w-0 flex-1 truncate text-[1.375rem] font-semibold leading-tight tracking-[-0.01em] text-fg',
            'text-center' => $align === 'center',
        ])>
            {{ $title ?? $slot }}
        </h1>

        @isset($action)
            <div class="shrink-0">{{ $action }}</div>
        @else
            @if ($back)
                <span class="h-9 w-9 shrink-0" aria-hidden="true"></span>
            @endif
        @endisset
    </div>
</header>
