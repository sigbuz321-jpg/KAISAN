<div class="flex items-center gap-3">
    @if ($glyph)
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md font-bold {{ $muted ? $tones['neutral'] : ($tones[$tone] ?? $tones['accent']) }}"
              aria-hidden="true">
            {{ $glyph }}
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <span class="block text-base font-semibold leading-snug text-fg">{{ $title }}</span>

        @if ($meta)
            <span class="mt-0.5 block text-xs leading-relaxed text-muted">{{ $meta }}</span>
        @endif
    </div>

    @if ($trail)
        <span class="shrink-0 text-xs uppercase tracking-[0.04em] text-muted">{{ $trail }}</span>
    @endif

    {{ $slot }}
</div>
