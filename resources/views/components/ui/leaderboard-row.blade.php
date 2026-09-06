@props([
    'rank',
    'name',
    'points',
    'meta' => null,
    'isMe' => false,
])

{{--
    Tahap 2 · Baris leaderboard.

    Rank 1 borrows the accent so the top of the table reads as brand; 2 and 3
    recede on purpose. The student's own row is outlined rather than recoloured,
    so it stays findable without competing with the podium.
--}}
@php
    $medal = match ((int) $rank) {
        1 => 'bg-master text-accent-fg',
        2 => 'bg-rank-2-soft text-neutral-text ring-1 ring-rank-2',
        3 => 'bg-rank-3-soft text-master-text ring-1 ring-rank-3',
        default => 'bg-surface-muted text-muted',
    };

    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
@endphp

<li @class([
    'flex items-center gap-3 border-b border-border px-3 py-3 last:border-b-0',
    'rounded-md border border-accent bg-accent-soft' => $isMe,
])>
    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $medal }}">
        {{ $rank }}
    </span>

    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent-text"
          aria-hidden="true">
        {{ $initial }}
    </span>

    <span class="min-w-0 flex-1">
        <span class="block truncate text-base font-medium text-fg">
            {{ $name }}@if ($isMe) <span class="text-sm font-normal text-muted">(kamu)</span>@endif
        </span>

        @if ($meta)
            <span class="mt-0.5 block text-xs text-muted">{{ $meta }}</span>
        @endif
    </span>

    <span class="shrink-0 text-right font-mono text-base font-semibold tabular-nums text-fg">
        {{ $points }}
    </span>
</li>
